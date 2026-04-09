<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\ConversationType;
use App\Enums\MessageKind;
use App\Enums\PerformerMembershipStatus;
use App\Events\Notifications\UserInAppNotificationsSynced;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\User;
use App\Notifications\Music\PerformerLineupInvitationNotification;
use App\Services\Music\PerformerMembershipService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PerformerMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    private PerformerMembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PerformerMembershipService::class);
    }

    public function test_invite_creates_pending_and_notifies_musician_user(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $inviter = $owner;
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->actingAs($inviter);
        $this->service->invite($peformer, $musician, $inviter);

        $peformer->refresh();
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(PerformerMembershipStatus::Pending, $row->pivot->status);

        Notification::assertSentTo(
            $musicianUser,
            PerformerLineupInvitationNotification::class,
            fn (PerformerLineupInvitationNotification $n) => $n->peformerId === $peformer->id
                && $n->musicianId === $musician->id,
        );
    }

    public function test_invite_posts_system_message_to_notice_messenger_feed(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);

        $notice = Conversation::query()
            ->where('type', ConversationType::Notice)
            ->where('created_by_user_id', $musicianUser->id)
            ->first();
        $this->assertNotNull($notice);

        $msg = Message::query()
            ->where('conversation_id', $notice->id)
            ->where('kind', MessageKind::System)
            ->latest('id')
            ->first();
        $this->assertNotNull($msg);
        $this->assertStringContainsString($peformer->name, $msg->body);
        $expectedUrl = route('music.profiles', ['tab' => 'musician'], true).'#music-musician-lineup';
        $this->assertStringContainsString($expectedUrl, $msg->body);
    }

    public function test_invite_throws_when_musician_has_no_user(): void
    {
        $owner = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician(null);

        $this->expectException(ValidationException::class);

        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);
    }

    public function test_invite_throws_when_already_accepted(): void
    {
        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $peformer->musicians()->attach($musician->id, [
            'status' => PerformerMembershipStatus::Accepted->value,
            'show_on_musician_profile' => false,
            'invited_by_user_id' => $owner->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);
    }

    public function test_invite_throws_when_already_pending(): void
    {
        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $peformer->musicians()->attach($musician->id, [
            'status' => PerformerMembershipStatus::Pending->value,
            'show_on_musician_profile' => false,
            'invited_by_user_id' => $owner->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);
    }

    public function test_invite_after_decline_resets_to_pending(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $peformer->musicians()->attach($musician->id, [
            'status' => PerformerMembershipStatus::Declined->value,
            'show_on_musician_profile' => false,
            'invited_by_user_id' => $owner->id,
            'responded_at' => now(),
        ]);

        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);

        $peformer->refresh();
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        $this->assertSame(PerformerMembershipStatus::Pending, $row->pivot->status);
        $this->assertNull($row->pivot->responded_at);
    }

    public function test_invite_throws_when_inviter_cannot_manage_members(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->expectException(AuthorizationException::class);

        $this->actingAs($stranger);
        $this->service->invite($peformer, $musician, $stranger);
    }

    public function test_accept_moves_pending_to_accepted_and_marks_notifications_read(): void
    {
        Event::fake([UserInAppNotificationsSynced::class]);

        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);

        $musicianUser->refresh();
        $this->assertGreaterThan(0, $musicianUser->unreadNotifications()->count());

        $this->actingAs($musicianUser);
        $this->service->accept($peformer, $musician, $musicianUser);

        $peformer->refresh();
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        $this->assertSame(PerformerMembershipStatus::Accepted, $row->pivot->status);
        $this->assertNotNull($row->pivot->responded_at);

        $musicianUser->refresh();
        $this->assertSame(0, $musicianUser->unreadNotifications()->count());

        Event::assertDispatched(UserInAppNotificationsSynced::class);
    }

    public function test_accept_throws_when_not_musician_owner(): void
    {
        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $peformer->musicians()->attach($musician->id, [
            'status' => PerformerMembershipStatus::Pending->value,
            'show_on_musician_profile' => false,
            'invited_by_user_id' => $owner->id,
        ]);

        $this->actingAs($otherUser);
        $this->expectException(AuthorizationException::class);
        $this->service->accept($peformer, $musician, $otherUser);
    }

    public function test_accept_throws_when_no_pending_invitation(): void
    {
        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->actingAs($musicianUser);
        $this->expectException(ValidationException::class);
        $this->service->accept($peformer, $musician, $musicianUser);
    }

    public function test_decline_sets_declined_and_triggers_sync(): void
    {
        Event::fake([UserInAppNotificationsSynced::class]);

        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);

        $this->actingAs($musicianUser);
        $this->service->decline($peformer, $musician, $musicianUser);

        $peformer->refresh();
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        $this->assertSame(PerformerMembershipStatus::Declined, $row->pivot->status);

        Event::assertDispatched(UserInAppNotificationsSynced::class);
    }

    public function test_leave_sets_left_from_accepted(): void
    {
        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $peformer->musicians()->attach($musician->id, [
            'status' => PerformerMembershipStatus::Accepted->value,
            'show_on_musician_profile' => false,
            'invited_by_user_id' => $owner->id,
            'responded_at' => now(),
        ]);

        $this->actingAs($musicianUser);
        $this->service->leave($peformer, $musician, $musicianUser);

        $peformer->refresh();
        $row = $peformer->musicians()->whereKey($musician->id)->first();
        $this->assertSame(PerformerMembershipStatus::Left, $row->pivot->status);
    }

    public function test_cancel_pending_detaches_and_syncs_notifications(): void
    {
        Event::fake([UserInAppNotificationsSynced::class]);

        $owner = User::factory()->create();
        $musicianUser = User::factory()->create();
        $peformer = $this->createPeformer($owner);
        $musician = $this->createMusician($musicianUser);

        $this->actingAs($owner);
        $this->service->invite($peformer, $musician, $owner);

        $this->service->cancelPending($peformer, $musician, $owner);

        $peformer->refresh();
        $this->assertNull($peformer->musicians()->whereKey($musician->id)->first());

        Event::assertDispatched(UserInAppNotificationsSynced::class);
    }

    private function createPeformer(User $owner): Peformer
    {
        return Peformer::query()->create([
            'name' => 'Band '.uniqid('', true),
            'description' => 'Description',
            'owner_user_id' => $owner->id,
            'slug' => 'band-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);
    }

    private function createMusician(?User $user): Musician
    {
        return Musician::query()->create([
            'name' => 'Musician '.uniqid('', true),
            'description' => 'Bio',
            'user_id' => $user?->id,
            'slug' => 'mus-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);
    }
}
