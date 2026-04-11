<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Models\ConcertVenue;
use App\Models\MusicProfileMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ConcertVenuePolicyMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_representative_has_read_only_access(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $venue = ConcertVenue::query()->create([
            'name' => 'Policy Venue',
            'owner_user_id' => $owner->id,
        ]);

        MusicProfileMembership::query()->create([
            'member_user_id' => $member->id,
            'entity_type' => ConcertVenue::class,
            'entity_id' => $venue->id,
            'role' => MusicMembershipRole::VenueRepresentative,
            'status' => MusicMembershipStatus::Pending,
            'invited_by_user_id' => $owner->id,
        ]);

        $this->assertTrue(Gate::forUser($member)->allows('view', $venue));
        $this->assertFalse(Gate::forUser($member)->allows('update', $venue));
        $this->assertFalse(Gate::forUser($member)->allows('manageMatching', $venue));
    }
}
