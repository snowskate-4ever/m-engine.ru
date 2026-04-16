<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\NotificationTopic;
use App\Models\User;
use App\Services\Analytics\ProductMetricsService;

/**
 * Единая точка выбора каналов доставки по пользовательским notification_preferences.
 */
final class NotificationGateway
{
    /**
     * @return list<string>
     */
    public function channels(User $user, NotificationTopic $topic): array
    {
        $channels = [];

        if ($user->wantsInAppNotifications()) {
            $channels[] = 'database';
        }

        if ($topic === NotificationTopic::LineupInvitation && $user->wantsMusicLineupInvitationEmail()) {
            $channels[] = 'mail';
        }

        $channels = array_values(array_unique($channels));

        if ($channels === [] && config('observability.record_notification_gateway_metrics', true)) {
            app(ProductMetricsService::class)->track('notification.gateway.empty_channels', $user->id, 'notifications', [
                'topic' => $topic->value,
            ]);
        }

        return $channels;
    }
}
