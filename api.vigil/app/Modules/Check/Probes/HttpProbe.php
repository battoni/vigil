<?php

namespace App\Modules\Check\Probes;

use App\Modules\Check\Support\ProbeResult;
use App\Modules\Check\Support\SsrfException;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class HttpProbe implements ProbeInterface
{
    private const MAX_REDIRECTS = 5;

    public function __construct(
        private SsrfGuard $ssrfGuard,
    ) {}

    public function run(Monitor $monitor, int $timeoutMs): ProbeResult
    {
        $config = $monitor->config ?? [];
        $method = strtoupper($config['method'] ?? 'GET');
        $startedAt = hrtime(true);

        try {
            $response = $this->dispatch($method, $monitor->target, $config, $timeoutMs, self::MAX_REDIRECTS);
        } catch (SsrfException $exception) {
            return ProbeResult::down('ssrf_blocked: '.$exception->getMessage());
        } catch (ConnectionException) {
            return ProbeResult::down('timeout', $this->elapsedMs($startedAt));
        } catch (Throwable $exception) {
            return ProbeResult::down('connection_error: '.$exception->getMessage(), $this->elapsedMs($startedAt));
        }

        return $this->evaluate($response, $config, $this->elapsedMs($startedAt));
    }

    /**
     * Issue the request to the original URL while pinning the connection to a
     * vetted public IP, manually following (and re-vetting) each redirect hop.
     */
    private function dispatch(string $method, string $url, array $config, int $timeoutMs, int $redirectsLeft): Response
    {
        $pinned = $this->ssrfGuard->resolveAndPin($url);
        $parts = parse_url($url);
        $port = $parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80);

        $request = Http::timeout(max(1.0, $timeoutMs / 1000))
            ->withoutRedirecting()
            ->withOptions(['curl' => [CURLOPT_RESOLVE => ["{$pinned->host}:{$port}:{$pinned->ip}"]]]);

        if (! empty($config['headers']) && is_array($config['headers'])) {
            $request->withHeaders($config['headers']);
        }

        if (! empty($config['basic_auth']) && is_array($config['basic_auth'])) {
            $request->withBasicAuth($config['basic_auth']['username'] ?? '', $config['basic_auth']['password'] ?? '');
        }

        $response = $request->send($method, $url, $this->bodyOptions($config));

        if ($this->isRedirect($response) && $redirectsLeft > 0) {
            $location = $response->header('Location');

            if ($location !== '') {
                return $this->dispatch($method, $this->resolveLocation($url, $location), $config, $timeoutMs, $redirectsLeft - 1);
            }
        }

        return $response;
    }

    private function evaluate(Response $response, array $config, int $elapsedMs): ProbeResult
    {
        $status = $response->status();
        $expectedStatus = $config['expected_status'] ?? null;

        if ($expectedStatus !== null && $status !== (int) $expectedStatus) {
            return ProbeResult::down("unexpected_status: {$status}", $elapsedMs, $status);
        }

        if ($expectedStatus === null && $status >= 400) {
            return ProbeResult::down("http_error: {$status}", $elapsedMs, $status);
        }

        if (! empty($config['keyword'])) {
            $isPresent = str_contains($response->body(), $config['keyword']);
            $shouldBePresent = $config['keyword_should_be_present'] ?? true;

            if ($isPresent !== $shouldBePresent) {
                return ProbeResult::down('keyword_mismatch', $elapsedMs, $status);
            }
        }

        if (! empty($config['json_path'])) {
            if ($response->json($config['json_path']) === null) {
                return ProbeResult::down('json_path_missing', $elapsedMs, $status);
            }
        }

        return ProbeResult::up($elapsedMs, $status);
    }

    private function bodyOptions(array $config): array
    {
        if (! empty($config['body'])) {
            return is_array($config['body']) ? ['json' => $config['body']] : ['body' => $config['body']];
        }

        return [];
    }

    private function isRedirect(Response $response): bool
    {
        return $response->status() >= 300 && $response->status() < 400 && $response->header('Location') !== '';
    }

    private function resolveLocation(string $base, string $location): string
    {
        if (str_contains($location, '://')) {
            return $location;
        }

        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        return "{$scheme}://{$host}{$port}/".ltrim($location, '/');
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) ((hrtime(true) - $startedAt) / 1_000_000);
    }
}
