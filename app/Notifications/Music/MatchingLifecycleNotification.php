<?php

declare(strict_types=1);

namespace App\Notifications\Music;

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
        return ['database'];
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
