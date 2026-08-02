<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class IntegrationErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $serviceName,
        public string $errorMessage,
        public array $payload = []
    ) {}

    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable)
    {
        $chatId = config('services.telegram-bot-api.manager_chat_id');

        $formattedPayload = json_encode($this->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return TelegramMessage::create()
            ->to($chatId)
            ->content(
                "🚨 *Ошибка в сервисе:* `{$this->serviceName}`\n\n" .
                "⚠️ *Текст ошибки:*\n`{$this->errorMessage}`\n\n" .
                "📦 *Payload:*\n```json\n{$formattedPayload}\n```"
            );
    }
}
