<?php

namespace App\Modules\Check\Support;

use App\Modules\Monitor\Enums\MonitorStatus;

/**
 * Result of applying a ProbeResult to a monitor's state machine. Describes
 * whether a confirmed UP/DOWN transition occurred and whether alerting should
 * be suppressed (active maintenance window).
 */
class StateTransition
{
    private function __construct(
        public readonly bool $transitioned,
        public readonly ?MonitorStatus $to = null,
        public readonly ?string $cause = null,
        public readonly bool $alertSuppressed = false,
    ) {}

    public static function none(): self
    {
        return new self(transitioned: false);
    }

    public static function wentDown(string $cause, bool $alertSuppressed): self
    {
        return new self(transitioned: true, to: MonitorStatus::DOWN, cause: $cause, alertSuppressed: $alertSuppressed);
    }

    public static function recovered(bool $alertSuppressed): self
    {
        return new self(transitioned: true, to: MonitorStatus::UP, alertSuppressed: $alertSuppressed);
    }

    public function isDown(): bool
    {
        return $this->to === MonitorStatus::DOWN;
    }

    public function isUp(): bool
    {
        return $this->to === MonitorStatus::UP;
    }

    public function shouldAlert(): bool
    {
        return $this->transitioned && ! $this->alertSuppressed;
    }
}
