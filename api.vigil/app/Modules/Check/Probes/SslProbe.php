<?php

namespace App\Modules\Check\Probes;

use App\Modules\Check\Support\ProbeResult;
use App\Modules\Check\Support\SsrfException;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Monitor\Models\Monitor;
use Throwable;

class SslProbe implements ProbeInterface
{
    private const DEFAULT_WARN_DAYS = 14;

    public function __construct(
        private SsrfGuard $ssrfGuard,
    ) {}

    public function run(Monitor $monitor, int $timeoutMs): ProbeResult
    {
        $config = $monitor->config ?? [];
        $warnDays = (int) ($config['ssl_warn_days'] ?? self::DEFAULT_WARN_DAYS);
        [$host, $port] = $this->splitTarget($monitor->target);

        try {
            $pinned = $this->ssrfGuard->resolveAndPin($host);
        } catch (SsrfException $exception) {
            return ProbeResult::down('ssrf_blocked: '.$exception->getMessage());
        }

        $startedAt = hrtime(true);

        try {
            $certificate = $this->fetchCertificate($host, $pinned->ip, $port, $timeoutMs / 1000);
        } catch (Throwable $exception) {
            return ProbeResult::down('tls_error: '.$exception->getMessage(), $this->elapsedMs($startedAt));
        }

        $elapsedMs = $this->elapsedMs($startedAt);
        $expiresAt = $certificate['valid_to'] ?? null;

        if ($expiresAt === null) {
            return ProbeResult::down('certificate_unreadable', $elapsedMs);
        }

        $daysRemaining = (int) floor(($expiresAt - time()) / 86400);

        if ($daysRemaining < 0) {
            return ProbeResult::down('certificate_expired', $elapsedMs);
        }

        if ($daysRemaining < $warnDays) {
            return ProbeResult::down("certificate_expiring: {$daysRemaining}d", $elapsedMs);
        }

        return ProbeResult::up($elapsedMs);
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function splitTarget(string $target): array
    {
        if (str_contains($target, '://')) {
            $parts = parse_url($target);

            return [$parts['host'] ?? $target, $parts['port'] ?? 443];
        }

        if (preg_match('/^(.+):(\d+)$/', $target, $matches) && substr_count($target, ':') === 1) {
            return [$matches[1], (int) $matches[2]];
        }

        return [$target, 443];
    }

    /**
     * Overridable for tests. Returns ['valid_to' => unix-timestamp].
     *
     * @return array{valid_to: int|null}
     */
    protected function fetchCertificate(string $host, string $ip, int $port, float $timeoutSeconds): array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'SNI_enabled' => true,
                'peer_name' => $host,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$ip}:{$port}",
            $errorCode,
            $errorMessage,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client === false) {
            throw new \RuntimeException($errorMessage ?: 'tls_connect_failed');
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if ($certificate === null) {
            return ['valid_to' => null];
        }

        $parsed = openssl_x509_parse($certificate);

        return ['valid_to' => $parsed['validTo_time_t'] ?? null];
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) ((hrtime(true) - $startedAt) / 1_000_000);
    }
}
