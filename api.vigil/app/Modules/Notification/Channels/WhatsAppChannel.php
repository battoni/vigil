<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Support\AlertMessage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends via a self-hosted Evolution API instance (internal-only). Config:
 * base_url, api_key, instance, recipients[].
 */
class WhatsAppChannel implements NotifierInterface
{
    public function notify(NotificationChannel $channel, AlertMessage $message): void
    {
        $config = $channel->config ?? [];
        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        $instance = $config['instance'] ?? null;
        $apiKey = $config['api_key'] ?? '';
        $recipients = $config['recipients'] ?? [];

        if (empty($baseUrl) || empty($instance) || empty($recipients)) {
            throw new RuntimeException('WhatsApp channel is missing base_url, instance or recipients.');
        }

        foreach ($recipients as $recipient) {
            $response = Http::timeout(10)
                ->withHeaders(['apikey' => $apiKey])
                ->post("{$baseUrl}/message/sendText/{$instance}", [
                    'number' => $recipient,
                    'text' => $message->text(),
                ]);

            if ($response->failed()) {
                throw new RuntimeException("Evolution API failed for {$recipient} with status {$response->status()}.");
            }
        }
    }
}
