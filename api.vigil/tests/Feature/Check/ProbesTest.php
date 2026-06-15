<?php

use App\Modules\Check\Probes\HttpProbe;
use App\Modules\Check\Probes\ProbeFactory;
use App\Modules\Check\Probes\SslProbe;
use App\Modules\Check\Probes\TcpProbe;
use App\Modules\Check\Support\SsrfGuard;
use App\Modules\Monitor\Enums\MonitorType;
use App\Modules\Monitor\Models\Monitor;
use Illuminate\Support\Facades\Http;

function publicGuard(): SsrfGuard
{
    return new SsrfGuard(fn (string $host) => ['203.0.113.10']);
}

function blockedGuard(): SsrfGuard
{
    return new SsrfGuard(fn (string $host) => ['10.0.0.5']);
}

function makeMonitor(MonitorType $type, string $target, array $config = []): Monitor
{
    $monitor = new Monitor;
    $monitor->type = $type;
    $monitor->target = $target;
    $monitor->config = $config;

    return $monitor;
}

// HTTP ----------------------------------------------------------------------

it('reports up for a healthy http response', function () {
    Http::fake(['*' => Http::response('hello', 200)]);

    $result = (new HttpProbe(publicGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://example.com', ['expected_status' => 200]),
        10000
    );

    expect($result->up)->toBeTrue()->and($result->statusCode)->toBe(200);
});

it('reports down when the status code is unexpected', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $result = (new HttpProbe(publicGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://example.com', ['expected_status' => 200]),
        10000
    );

    expect($result->up)->toBeFalse()
        ->and($result->statusCode)->toBe(500)
        ->and($result->error)->toContain('unexpected_status');
});

it('reports down on a 4xx/5xx when no expected status is set', function () {
    Http::fake(['*' => Http::response('', 503)]);

    $result = (new HttpProbe(publicGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://example.com'),
        10000
    );

    expect($result->up)->toBeFalse()->and($result->error)->toContain('http_error');
});

it('reports up when a required keyword is present', function () {
    Http::fake(['*' => Http::response('status: operational', 200)]);

    $result = (new HttpProbe(publicGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://example.com', ['keyword' => 'operational']),
        10000
    );

    expect($result->up)->toBeTrue();
});

it('reports down when a required keyword is missing', function () {
    Http::fake(['*' => Http::response('status: degraded', 200)]);

    $result = (new HttpProbe(publicGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://example.com', ['keyword' => 'operational']),
        10000
    );

    expect($result->up)->toBeFalse()->and($result->error)->toBe('keyword_mismatch');
});

it('follows redirects to a healthy final response', function () {
    Http::fake([
        'https://example.com/start' => Http::response('', 302, ['Location' => 'https://example.com/final']),
        'https://example.com/final' => Http::response('ok', 200),
    ]);

    $result = (new HttpProbe(publicGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://example.com/start'),
        10000
    );

    expect($result->up)->toBeTrue();
});

it('reports down without calling http when the target is ssrf-blocked', function () {
    Http::fake(['*' => Http::response('should not be called', 200)]);

    $result = (new HttpProbe(blockedGuard()))->run(
        makeMonitor(MonitorType::HTTP, 'https://internal.example.com'),
        10000
    );

    expect($result->up)->toBeFalse()->and($result->error)->toContain('ssrf_blocked');
    Http::assertNothingSent();
});

// TCP -----------------------------------------------------------------------

it('reports up when the tcp socket connects', function () {
    $probe = new class(publicGuard()) extends TcpProbe
    {
        protected function openSocket(string $ip, int $port, float $timeoutSeconds): array
        {
            return [true, null];
        }
    };

    $result = $probe->run(makeMonitor(MonitorType::TCP, 'db.example.com:5432'), 10000);

    expect($result->up)->toBeTrue();
});

it('reports down when the tcp socket is refused', function () {
    $probe = new class(publicGuard()) extends TcpProbe
    {
        protected function openSocket(string $ip, int $port, float $timeoutSeconds): array
        {
            return [false, 'connection_refused'];
        }
    };

    $result = $probe->run(makeMonitor(MonitorType::TCP, 'db.example.com:5432'), 10000);

    expect($result->up)->toBeFalse()->and($result->error)->toBe('connection_refused');
});

it('reports invalid target when the tcp port is missing', function () {
    $result = (new TcpProbe(publicGuard()))->run(makeMonitor(MonitorType::TCP, 'db.example.com'), 10000);

    expect($result->up)->toBeFalse()->and($result->error)->toContain('invalid_target');
});

// SSL -----------------------------------------------------------------------

it('reports up when the certificate is valid and not near expiry', function () {
    $probe = new class(publicGuard()) extends SslProbe
    {
        protected function fetchCertificate(string $host, string $ip, int $port, float $timeoutSeconds): array
        {
            return ['valid_to' => time() + (60 * 86400)];
        }
    };

    $result = $probe->run(makeMonitor(MonitorType::SSL, 'https://example.com', ['ssl_warn_days' => 14]), 10000);

    expect($result->up)->toBeTrue();
});

it('reports down when the certificate is near expiry', function () {
    $probe = new class(publicGuard()) extends SslProbe
    {
        protected function fetchCertificate(string $host, string $ip, int $port, float $timeoutSeconds): array
        {
            return ['valid_to' => time() + (5 * 86400)];
        }
    };

    $result = $probe->run(makeMonitor(MonitorType::SSL, 'https://example.com', ['ssl_warn_days' => 14]), 10000);

    expect($result->up)->toBeFalse()->and($result->error)->toContain('certificate_expiring');
});

it('reports down when the certificate is expired', function () {
    $probe = new class(publicGuard()) extends SslProbe
    {
        protected function fetchCertificate(string $host, string $ip, int $port, float $timeoutSeconds): array
        {
            return ['valid_to' => time() - 86400];
        }
    };

    $result = $probe->run(makeMonitor(MonitorType::SSL, 'https://example.com'), 10000);

    expect($result->up)->toBeFalse()->and($result->error)->toBe('certificate_expired');
});

// FACTORY -------------------------------------------------------------------

it('resolves the right probe per monitor type', function () {
    $factory = app(ProbeFactory::class);

    expect($factory->for(MonitorType::HTTP))->toBeInstanceOf(HttpProbe::class)
        ->and($factory->for(MonitorType::KEYWORD))->toBeInstanceOf(HttpProbe::class)
        ->and($factory->for(MonitorType::TCP))->toBeInstanceOf(TcpProbe::class)
        ->and($factory->for(MonitorType::SSL))->toBeInstanceOf(SslProbe::class)
        ->and($factory->supports(MonitorType::PING))->toBeFalse();
});

it('throws for an unsupported probe type', function () {
    expect(fn () => app(ProbeFactory::class)->for(MonitorType::PING))
        ->toThrow(InvalidArgumentException::class);
});
