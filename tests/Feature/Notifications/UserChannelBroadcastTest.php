<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Events\Notifications\UserInAppNotificationsSynced;
use App\Events\Notifications\UserNotificationCreated;
use App\Models\User;
use App\Notifications\Music\PerformerLineupInvitationNotification;
use App\Services\Notifications\InAppNotificationSyncBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserChannelBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_notification_dispatches_user_notification_created_on_private_user_channel(): void
    {
        Event::fake([UserNotificationCreated::class]);

        $user = User::factory()->create();

        $user->notify(new PerformerLineupInvitationNotification(
            peformerId: 1,
            musicianId: 1,
            peformerName: 'Test Ensemble',
            inviterName: 'Inviter',
        ));

        Event::assertDispatched(UserNotificationCreated::class, function (UserNotificationCreated $e) use ($user): bool {
            $channelNames = array_map(
                static fn ($ch) => $ch->name,
                $e->broadcastOn(),
            );

            return $e->userId === (int) $user->id
                && $e->broadcastAs() === 'user.notification.created'
                && in_array('private-user.'.$user->id, $channelNames, true)
                && ($e->notification['type'] ?? '') === PerformerLineupInvitationNotification::class
                && str_contains((string) ($e->notification['action_url'] ?? ''), '#music-musician-lineup');
        });
    }

    public function test_in_app_sync_broadcaster_dispatches_user_notifications_synced_on_private_user_channel(): void
    {
        Event::fake([UserInAppNotificationsSynced::class]);

        $user = User::factory()->create();

        app(InAppNotificationSyncBroadcaster::class)->sync($user, true);

        Event::assertDispatched(UserInAppNotificationsSynced::class, function (UserInAppNotificationsSynced $e) use ($user): bool {
            $channelNames = array_map(
                static fn ($ch) => $ch->name,
                $e->broadcastOn(),
            );

            return $e->userId === (int) $user->id
                && $e->broadcastAs() === 'user.notifications.synced'
                && in_array('private-user.'.$user->id, $channelNames, true)
                && $e->unreadCount === 0
                && $e->refreshPreview === true
                && $e->notificationId === null
                && $e->readAtIso === null;
        });
    }
}
