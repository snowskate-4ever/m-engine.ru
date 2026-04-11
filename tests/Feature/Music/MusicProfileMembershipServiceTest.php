<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Models\ConcertVenue;
use App\Models\User;
use App\Services\Music\MusicProfileMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusicProfileMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_and_member_can_accept(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $venue = ConcertVenue::query()->create([
            'name' => 'Venue One',
            'owner_user_id' => $owner->id,
        ]);

        $membership = app(MusicProfileMembershipService::class)->invite(
            $owner,
            $venue,
            $member,
            MusicMembershipRole::VenueRepresentative,
        );

        $this->assertSame(MusicMembershipStatus::Pending->value, $membership->status->value);

        $accepted = app(MusicProfileMembershipService::class)->respond(
            $member,
            $membership->fresh(),
            MusicMembershipStatus::Accepted,
        );

        $this->assertSame(MusicMembershipStatus::Accepted->value, $accepted->status->value);
    }

    public function test_owner_can_revoke_membership(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $venue = ConcertVenue::query()->create([
            'name' => 'Venue Two',
            'owner_user_id' => $owner->id,
        ]);

        $membership = app(MusicProfileMembershipService::class)->invite(
            $owner,
            $venue,
            $member,
            MusicMembershipRole::VenueRepresentative,
        );
        app(MusicProfileMembershipService::class)->revoke($owner, $membership->fresh());

        $this->assertDatabaseHas('music_profile_memberships', [
            'id' => $membership->id,
            'status' => MusicMembershipStatus::Revoked->value,
        ]);
    }
}
