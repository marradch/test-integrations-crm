<?php

namespace App\Listeners;

use App\Events\IntegrationErrorOccurred;
use App\Notifications\IntegrationErrorNotification;
use Illuminate\Support\Facades\Notification;

class SendTelegramErrorNotificationListener
{
    /**
     * Handle the event.
     */
    public function handle(IntegrationErrorOccurred $event): void
    {
        Notification::send(
            new \Illuminate\Notifications\AnonymousNotifiable(),
            new IntegrationErrorNotification(
                $event->serviceName,
                $event->errorMessage,
                $event->payload
            )
        );
    }
}
