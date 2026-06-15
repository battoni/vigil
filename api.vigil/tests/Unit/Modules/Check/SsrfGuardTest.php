<?php

use App\Modules\Check\Support\SsrfException;
use App\Modules\Check\Support\SsrfGuard;

function guardWithResolver(array $map): SsrfGuard
{
    return new SsrfGuard(fn (string $host) => $map[$host] ?? []);
}

it('classifies blocked IPv4 addresses as non-public', function (string $ip) {
    expect((new SsrfGuard)->isPublicIp($ip))->toBeFalse();
})->with([
    '127.0.0.1', '10.0.0.1', '172.16.0.1', '192.168.1.10',
    '169.254.169.254', '100.64.0.1', '0.0.0.0',
]);

it('classifies blocked IPv6 addresses as non-public', function (string $ip) {
    expect((new SsrfGuard)->isPublicIp($ip))->toBeFalse();
})->with([
    '::1', 'fe80::1', 'fc00::1', '::ffff:127.0.0.1', '::ffff:10.0.0.1',
]);

it('classifies real public addresses as public', function (string $ip) {
    expect((new SsrfGuard)->isPublicIp($ip))->toBeTrue();
})->with([
    '8.8.8.8', '1.1.1.1', '2606:4700:4700::1111',
]);

it('rejects obviously internal targets at create-time without DNS', function (string $target) {
    expect(fn () => (new SsrfGuard)->assertSafe($target))->toThrow(SsrfException::class);
})->with([
    'http://127.0.0.1/health',
    'http://localhost:8080',
    'http://169.254.169.254/latest/meta-data',
    'https://service.internal',
    'tcp://10.0.0.5:5432',
    '192.168.0.1:6379',
]);

it('allows well-formed public targets at create-time', function (string $target) {
    expect(fn () => (new SsrfGuard)->assertSafe($target))->not->toThrow(SsrfException::class);
})->with([
    'https://example.com',
    'https://api.example.com/health',
    'https://8.8.8.8',
    'example.com:443',
]);

it('pins the resolved public IP for a hostname', function () {
    $guard = guardWithResolver(['api.example.com' => ['203.0.113.10']]);

    $pinned = $guard->resolveAndPin('https://api.example.com/health');

    expect($pinned->host)->toBe('api.example.com')
        ->and($pinned->ip)->toBe('203.0.113.10');
});

it('rejects a hostname that resolves to a private address (rebinding)', function () {
    $guard = guardWithResolver(['evil.example.com' => ['10.0.0.7']]);

    expect(fn () => $guard->resolveAndPin('https://evil.example.com'))
        ->toThrow(SsrfException::class);
});

it('rejects when any resolved address is blocked, even if another is public', function () {
    $guard = guardWithResolver(['mixed.example.com' => ['8.8.8.8', '169.254.169.254']]);

    expect(fn () => $guard->resolveAndPin('https://mixed.example.com'))
        ->toThrow(SsrfException::class);
});

it('throws when a hostname cannot be resolved', function () {
    $guard = guardWithResolver(['nope.example.com' => []]);

    expect(fn () => $guard->resolveAndPin('https://nope.example.com'))
        ->toThrow(SsrfException::class);
});

it('pins a literal public IP target and rejects a literal private one', function () {
    $guard = new SsrfGuard;

    expect($guard->resolveAndPin('https://203.0.113.5')->ip)->toBe('203.0.113.5')
        ->and(fn () => $guard->resolveAndPin('http://192.168.1.1'))->toThrow(SsrfException::class);
});

it('re-checks a single IP for redirect hops', function () {
    $guard = new SsrfGuard;

    expect(fn () => $guard->assertIpSafe('203.0.113.9'))->not->toThrow(SsrfException::class)
        ->and(fn () => $guard->assertIpSafe('127.0.0.1'))->toThrow(SsrfException::class);
});
