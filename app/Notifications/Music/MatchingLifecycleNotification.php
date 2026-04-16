<?php

declare(strict_types=1);

namespace App\Notifications\Music;

use App\Enums\NotificationTopic;
use App\Models\User;
use App\Services\Notifications\NotificationGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MatchingLifecycleNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $titleKey,
        private readonly string $bodyKey,
        private readonly array $payload = [],
    ) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        return app(NotificationGateway::class)->channels($notifiable, NotificationTopic::MatchingLifecycle);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_key' => $this->titleKey,
            'body_key' => $this->bodyKey,
            'payload' => $this->payload,
        ];
    }
}
