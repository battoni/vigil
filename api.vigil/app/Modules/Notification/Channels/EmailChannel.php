<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Support\AlertMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Email is itself a mini fallback chain: Resend (HTTP API) → SMTP. Kept on a
 * dedicated sending identity so alert mail never shares fate with monitored
 * sites. See PLAN.md §6.
 */
class EmailChannel implements NotifierInterface
{
    public function notify(NotificationChannel $channel, AlertMessage $message): void
    {
        $config = $channel->config ?? [];
        $recipients = $config['recipients'] ?? [];

        if (empty($recipients)) {
            throw new RuntimeException('Email channel is missing recipients.');
        }

        try {
            $this->sendViaResend($config, $recipients, $message);

            return;
        } catch (Throwable) {
            // Fall through to SMTP — the email sub-chain.
        }

        $this->sendViaSmtp($recipients, $message);
    }

    protected function sendViaResend(array $config, array $recipients, AlertMessage $message): void
    {
        $apiKey = $config['resend_api_key'] ?? null;
        $from = $config['from'] ?? null;

        if (empty($apiKey) || empty($from)) {
            throw new RuntimeException('Resend is not configured for this channel.');
        }

        $response = Http::withToken($apiKey)->timeout(10)->post('https://api.resend.com/emails', [
            'from' => $from,
            'to' => $recipients,
            'subject' => $message->subject(),
            'text' => $message->text(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Resend failed with status {$response->status()}.");
        }
    }

    protected function sendViaSmtp(array $recipients, AlertMessage $message): void
    {
        Mail::raw($message->text(), function ($mail) use ($recipients, $message) {
            $mail->to($recipients)->subject($message->subject());
        });
    }
}
