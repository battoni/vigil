<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Support\AlertMessage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SlackChannel implements NotifierInterface
{
    public function notify(NotificationChannel $channel, AlertMessage $message): void
    {
        $webhookUrl = $channel->config['webhook_url'] ?? null;

        if (empty($webhookUrl)) {
            throw new RuntimeException('Slack channel is missing webhook_url.');
        }

        $response = Http::timeout(10)->post($webhookUrl, ['text' => $message->text()]);

        if ($response->failed()) {
            throw new RuntimeException("Slack webhook failed with status {$response->status()}.");
        }
    }
}
