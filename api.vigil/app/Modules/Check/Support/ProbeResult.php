<?php

namespace App\Modules\Check\Support;

/**
 * Immutable value object describing the outcome of a single probe run.
 */
class ProbeResult
{
    public function __construct(
        public readonly bool $up,
        public readonly ?int $responseTimeMs = null,
        public readonly ?int $statusCode = null,
        public readonly ?string $error = null,
    ) {}

    public static function up(?int $responseTimeMs = null, ?int $statusCode = null): self
    {
        return new self(up: true, responseTimeMs: $responseTimeMs, statusCode: $statusCode);
    }

    public static function down(string $error, ?int $responseTimeMs = null, ?int $statusCode = null): self
    {
        return new self(up: false, responseTimeMs: $responseTimeMs, statusCode: $statusCode, error: $error);
    }
}
