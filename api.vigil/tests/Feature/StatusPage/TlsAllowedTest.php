<?php

use App\Modules\StatusPage\Models\StatusPage;

it('allows a registered custom domain', function () {
    StatusPage::factory()->create(['custom_domain' => 'status.acme.com']);

    $this->getJson('/api/internal/tls-allowed?domain=status.acme.com')->assertOk();
});

it('rejects an unregistered domain', function () {
    $this->getJson('/api/internal/tls-allowed?domain=evil.example.com')->assertNotFound();
});

it('rejects a missing domain', function () {
    $this->getJson('/api/internal/tls-allowed')->assertNotFound();
});
