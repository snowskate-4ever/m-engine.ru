<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Events\Notifications\UserInAppNotificationsSynced;
use App\Models\User;

final class InAppNotificationSyncBroadcaster
{
    /**
     * @param  non-empty-string|null  $notificationId  UUID записи в notifications
     */
    public function sync(
        User $user,
        bool $refreshPreview = false,
        ?string $notificationId = null,
        ?string $readAtIso = null,
    ): void {
        $unread = $user->unreadNotifications()->count();

        broadcast(new UserInAppNotificationsSynced(
            userId: (int) $user->id,
            unreadCount: $unread,
            notificationId: $notificationId,
            readAtIso: $readAtIso,
            refreshPreview: $refreshPreview,
        ));
    }
}
