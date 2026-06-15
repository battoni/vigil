<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('pings the external dead-man url when configured', function () {
    config(['vigil.deadman_url' => 'https://hc-ping.com/abc-123']);
    Http::fake(['hc-ping.com/*' => Http::response('OK', 200)]);

    $this->artisan('monitoring:deadman-ping')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'hc-ping.com/abc-123'));
});

it('skips cleanly when the dead-man url is not configured', function () {
    config(['vigil.deadman_url' => null]);
    Http::fake();

    $this->artisan('monitoring:deadman-ping')->assertSuccessful();

    Http::assertNothingSent();
});

it('does not fail the tick when the external ping errors', function () {
    config(['vigil.deadman_url' => 'https://hc-ping.com/abc-123']);
    Http::fake(fn () => throw new ConnectionException('down'));

    $this->artisan('monitoring:deadman-ping')->assertSuccessful();
});
