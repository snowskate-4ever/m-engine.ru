<?php

declare(strict_types=1);

namespace App\Listeners\Notifications;

use App\Events\Notifications\UserNotificationCreated;
use App\Models\User;
use App\Services\Notifications\NotificationPresenter;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

final class BroadcastDatabaseNotification
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        $dbNotification = $event->response;
        if (! $dbNotification instanceof DatabaseNotification) {
            // Safety fallback: database channel should return DatabaseNotification, but don't fail the request/job.
            return;
        }

        event(new UserNotificationCreated(
            userId: (int) $event->notifiable->id,
            notification: app(NotificationPresenter::class)->toPublicArray($dbNotification),
        ));
    }
}
