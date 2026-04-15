<?php

declare(strict_types=1);

namespace App\Listeners\Notifications;

use App\Models\User;
use App\Services\Notifications\SystemNotificationMirrorService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

final class MirrorDatabaseNotificationToSystemChat
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        if (! $event->response instanceof DatabaseNotification) {
            return;
        }

        $payload = is_array($event->response->data) ? $event->response->data : [];
        app(SystemNotificationMirrorService::class)->mirror($event->notifiable, $payload);
    }
}
