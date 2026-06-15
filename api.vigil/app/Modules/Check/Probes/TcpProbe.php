<?php

namespace App\Modules\Check\Probes;

use App\Modules\Check\Support\ProbeResult;
use App\Modules\Check\Support\SsrfException;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Monitor\Models\Monitor;

class TcpProbe implements ProbeInterface
{
    public function __construct(
        private SsrfGuard $ssrfGuard,
    ) {}

    public function run(Monitor $monitor, int $timeoutMs): ProbeResult
    {
        [$host, $port] = $this->splitTarget($monitor->target, $monitor->config['port'] ?? null);

        if ($port === null) {
            return ProbeResult::down('invalid_target: missing port');
        }

        try {
            $pinned = $this->ssrfGuard->resolveAndPin($host);
        } catch (SsrfException $exception) {
            return ProbeResult::down('ssrf_blocked: '.$exception->getMessage());
        }

        $startedAt = hrtime(true);
        [$connected, $error] = $this->openSocket($pinned->ip, $port, $timeoutMs / 1000);
        $elapsedMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        return $connected
            ? ProbeResult::up($elapsedMs)
            : ProbeResult::down($error ?? 'connection_refused', $elapsedMs);
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function splitTarget(string $target, ?int $configPort): array
    {
        if (str_contains($target, '://')) {
            $parts = parse_url($target);

            return [$parts['host'] ?? $target, $parts['port'] ?? $configPort];
        }

        // Bare IPv6 literal — needs a configured port.
        if (substr_count($target, ':') > 1 && ! str_contains($target, '[')) {
            return [$target, $configPort];
        }

        if (preg_match('/^(.+):(\d+)$/', $target, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        return [$target, $configPort];
    }

    /**
     * Overridable for tests.
     *
     * @return array{0: bool, 1: string|null}
     */
    protected function openSocket(string $ip, int $port, float $timeoutSeconds): array
    {
        $connection = @fsockopen($ip, $port, $errorCode, $errorMessage, $timeoutSeconds);

        if ($connection === false) {
            return [false, $errorMessage ? "connection_failed: {$errorMessage}" : 'connection_refused'];
        }

        fclose($connection);

        return [true, null];
    }
}
