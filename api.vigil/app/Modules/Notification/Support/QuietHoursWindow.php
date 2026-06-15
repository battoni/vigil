<?php

namespace App\Modules\Notification\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * A daily quiet-hours window parsed from a monitor's config:
 * config.quiet_hours = { start: "22:00", end: "08:00", tz: "UTC" }.
 * Handles overnight windows where start > end. See PLAN.md §6.
 */
class QuietHoursWindow
{
    public function __construct(
        public readonly string $start,
        public readonly string $end,
        public readonly string $timezone = 'UTC',
    ) {}

    public static function fromConfig(?array $config): ?self
    {
        $quietHours = $config['quiet_hours'] ?? null;

        if (empty($quietHours['start']) || empty($quietHours['end'])) {
            return null;
        }

        return new self($quietHours['start'], $quietHours['end'], $quietHours['tz'] ?? 'UTC');
    }

    public function isActive(CarbonInterface $now): bool
    {
        $local = $now->copy()->setTimezone($this->timezone);
        $start = $local->copy()->setTimeFromTimeString($this->start);
        $end = $local->copy()->setTimeFromTimeString($this->end);

        if ($this->start <= $this->end) {
            return $local->betweenIncluded($start, $end);
        }

        // Overnight window (e.g. 22:00 → 08:00).
        return $local->gte($start) || $local->lte($end);
    }

    public function endsAt(CarbonInterface $now): Carbon
    {
        $local = $now->copy()->setTimezone($this->timezone);
        $end = $local->copy()->setTimeFromTimeString($this->end);

        if ($end->lte($local)) {
            $end->addDay();
        }

        return $end;
    }
}
