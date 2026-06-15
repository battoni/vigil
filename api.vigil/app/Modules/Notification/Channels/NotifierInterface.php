<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Models\NotificationChannel;
use App\Modules\Notification\Support\AlertMessage;

interface NotifierInterface
{
    /**
     * Deliver the message through the channel. MUST throw on any failure so the
     * dispatcher can fall through to the next channel in the chain.
     */
    public function notify(NotificationChannel $channel, AlertMessage $message): void;
}
