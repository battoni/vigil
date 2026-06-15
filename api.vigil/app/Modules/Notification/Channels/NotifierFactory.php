<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Enums\ChannelType;
use App\Modules\Notification\Models\NotificationChannel;
use Illuminate\Contracts\Container\Container;

class NotifierFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function for(NotificationChannel $channel): NotifierInterface
    {
        return match ($channel->type) {
            ChannelType::SLACK => $this->container->make(SlackChannel::class),
            ChannelType::WHATSAPP => $this->container->make(WhatsAppChannel::class),
            ChannelType::EMAIL => $this->container->make(EmailChannel::class),
        };
    }
}
