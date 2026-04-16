<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\Music\MatchingLifecycleNotification;
use App\Notifications\Music\PerformerLineupInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class NotificationGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_skips_send_when_in_app_disabled(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->setInAppNotifications(false);

        $user->notify(new MatchingLifecycleNotification('k.title', 'k.body', []));

        Notification::assertNothingSentTo($user);
    }

    public function test_matching_sends_when_in_app_enabled(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->notify(new MatchingLifecycleNotification('k.title', 'k.body', []));

        Notification::assertSentTo(
            $user,
            MatchingLifecycleNotification::class,
            static fn (MatchingLifecycleNotification $n, array $channels): bool => $channels === ['database'],
        );
    }

    public function test_lineup_can_send_mail_only_when_in_app_disabled(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->setInAppNotifications(false);
        $user->setMusicLineupInvitationEmail(true);

        $user->notify(new PerformerLineupInvitationNotification(
            peformerId: 1,
            musicianId: 2,
            peformerName: 'Band',
            inviterName: 'Owner',
        ));

        Notification::assertSentTo(
            $user,
            PerformerLineupInvitationNotification::class,
            static fn (PerformerLineupInvitationNotification $n, array $channels): bool => $channels === ['mail'],
        );
    }

    public function test_lineup_sends_database_and_mail_when_both_enabled(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->setInAppNotifications(true);
        $user->setMusicLineupInvitationEmail(true);

        $user->notify(new PerformerLineupInvitationNotification(
            peformerId: 1,
            musicianId: 2,
            peformerName: 'Band',
            inviterName: 'Owner',
        ));

        Notification::assertSentTo(
            $user,
            PerformerLineupInvitationNotification::class,
            static fn (PerformerLineupInvitationNotification $n, array $channels): bool => $channels === ['database', 'mail'],
        );
    }
}
