<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Analytics\ProductMetricsService;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;

final class LogNotificationFailed
{
    public function handle(NotificationFailed $event): void
    {
        Log::warning('notification.channel_failed', [
            'notifiable_type' => is_object($event->notifiable) ? $event->notifiable::class : null,
            'notifiable_id' => is_object($event->notifiable) && method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
            'notification' => is_object($event->notification) ? $event->notification::class : null,
            'channel' => $event->channel,
            'message' => $event->data['message'] ?? null,
            'exception' => isset($event->data['exception']) ? (string) $event->data['exception'] : null,
        ]);

        $userId = $event->notifiable instanceof User ? (int) $event->notifiable->getKey() : null;
        app(ProductMetricsService::class)->track('notification.delivery_failed', $userId, 'notifications', [
            'notification' => is_object($event->notification) ? $event->notification::class : null,
            'channel' => $event->channel,
        ]);
    }
}
