<?php

namespace App\Modules\Notification\Services;

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Monitor\Models\Monitor;
use App\Modules\Notification\Channels\NotifierFactory;
use App\Modules\Notification\Enums\ChannelType;
use App\Modules\Notification\Enums\NotificationStatus;
use App\Modules\Notification\Jobs\SendDeferredAlertJob;
use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Repositories\ChannelRepository;
use App\Modules\Notification\Repositories\NotificationLogRepository;
use App\Modules\Notification\Support\AlertMessage;
use App\Modules\Notification\Support\QuietHoursWindow;
use Throwable;

/**
 * Resilient alert delivery: a single alert is delivered through the first
 * channel in the WhatsApp → Slack → Email chain that succeeds. A failed channel
 * is logged and the chain falls through. An alert never fires twice for the same
 * incident event (dedup via notification_logs). See PLAN.md §6.
 */
class AlertDispatchService
{
    /** Fixed fallback priority. */
    private const PRIORITY = [
        ChannelType::WHATSAPP->value,
        ChannelType::SLACK->value,
        ChannelType::EMAIL->value,
    ];

    private const SEVERITY_RANK = ['info' => 1, 'warning' => 2, 'critical' => 3];

    public function __construct(
        private ChannelRepository $channelRepository,
        private NotificationLogRepository $notificationLogRepository,
        private NotifierFactory $notifierFactory,
    ) {}

    public function send(
        Monitor $monitor,
        IncidentEvent $event,
        ?Incident $incident = null,
        ?string $cause = null,
        bool $bypassQuietHours = false,
    ): bool {
        if ($incident !== null && $this->notificationLogRepository->hasDelivered($incident->id, $event)) {
            return true;
        }

        // Quiet hours DEFER non-critical alerts (never drop); critical bypasses.
        if (! $bypassQuietHours && $this->shouldDefer($monitor, $event)) {
            $this->defer($monitor, $event, $incident, $cause);

            return true;
        }

        $channels = $this->eligibleChannels($monitor, $event);
        $message = new AlertMessage($monitor, $event, $cause, $incident);

        foreach ($channels as $channel) {
            if ($this->deliver($channel, $message, $event, $incident)) {
                return true;
            }
        }

        if ($incident !== null) {
            $this->notificationLogRepository->recordLog($incident->id, null, $event, NotificationStatus::FAILED, 'all_channels_failed');
        }

        return false;
    }

    private function deliver(NotificationChannel $channel, AlertMessage $message, IncidentEvent $event, ?Incident $incident): bool
    {
        try {
            $this->notifierFactory->for($channel)->notify($channel, $message);

            if ($incident !== null) {
                $this->notificationLogRepository->recordLog($incident->id, $channel->id, $event, NotificationStatus::SENT);
            }

            return true;
        } catch (Throwable $exception) {
            if ($incident !== null) {
                $this->notificationLogRepository->recordLog($incident->id, $channel->id, $event, NotificationStatus::FAILED, $exception->getMessage());
            }

            return false;
        }
    }

    /**
     * @return NotificationChannel[]
     */
    private function eligibleChannels(Monitor $monitor, IncidentEvent $event): array
    {
        $excluded = $monitor->config['exclude_channels'] ?? [];

        $channels = array_filter(
            $this->channelRepository->findActiveForMonitor($monitor->id),
            fn (NotificationChannel $channel) => $this->isEligible($channel, $event, $excluded)
        );

        usort($channels, fn (NotificationChannel $a, NotificationChannel $b) => $this->priority($a) <=> $this->priority($b));

        return $channels;
    }

    private function isEligible(NotificationChannel $channel, IncidentEvent $event, array $excluded): bool
    {
        // Route around excluded channel types (e.g. the Evolution-health monitor skips WhatsApp).
        if (in_array($channel->type->value, $excluded, true)) {
            return false;
        }

        $pivot = $channel->monitors->first()?->pivot;

        if ($event === IncidentEvent::RECOVERED && ! (bool) ($pivot->notify_on_recovery ?? true)) {
            return false;
        }

        $minSeverity = $pivot->min_severity ?? null;

        if ($minSeverity !== null) {
            $minRank = self::SEVERITY_RANK[$minSeverity] ?? 1;

            return $this->severityRank($event) >= $minRank;
        }

        return true;
    }

    private function severityRank(IncidentEvent $event): int
    {
        return $event === IncidentEvent::RECOVERED ? self::SEVERITY_RANK['info'] : self::SEVERITY_RANK['critical'];
    }

    /**
     * Critical = DOWN / STILL_DOWN (always pages). RECOVERED is non-critical and
     * is deferred during a quiet-hours window.
     */
    private function isCritical(IncidentEvent $event): bool
    {
        return $event !== IncidentEvent::RECOVERED;
    }

    private function shouldDefer(Monitor $monitor, IncidentEvent $event): bool
    {
        if ($this->isCritical($event)) {
            return false;
        }

        $window = QuietHoursWindow::fromConfig($monitor->config);

        return $window !== null && $window->isActive(now());
    }

    private function defer(Monitor $monitor, IncidentEvent $event, ?Incident $incident, ?string $cause): void
    {
        $window = QuietHoursWindow::fromConfig($monitor->config);

        SendDeferredAlertJob::dispatch($monitor->id, $event->value, $incident?->id, $cause)
            ->delay($window->endsAt(now()));
    }

    private function priority(NotificationChannel $channel): int
    {
        $index = array_search($channel->type->value, self::PRIORITY, true);

        return $index === false ? count(self::PRIORITY) : $index;
    }
}
