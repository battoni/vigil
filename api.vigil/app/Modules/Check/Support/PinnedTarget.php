<?php

namespace App\Modules\Check\Support;

/**
 * A target whose hostname has been resolved to a single vetted public IP.
 * The probe connects to {@see $ip} while preserving {@see $host} for TLS/SNI
 * and the Host header, closing the DNS-rebinding / TOCTOU window.
 */
class PinnedTarget
{
    public function __construct(
        public readonly string $original,
        public readonly string $host,
        public readonly string $ip,
    ) {}
}
