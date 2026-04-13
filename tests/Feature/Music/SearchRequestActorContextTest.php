<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Enums\SearchGoal;
use App\Models\MusicProfileMembership;
use App\Models\Peformer;
use App\Models\User;
use App\Services\Music\SearchRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchRequestActorContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_request_for_delegated_performer(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create([
            'music_profiles' => ['manager'],
        ]);
        $peformer = Peformer::query()->create([
            'name' => 'Band Y',
            'owner_user_id' => $owner->id,
            'performer_kind' => 'band',
        ]);

        MusicProfileMembership::query()->create([
            'member_user_id' => $manager->id,
            'entity_type' => Peformer::class,
            'entity_id' => $peformer->id,
            'role' => MusicMembershipRole::Manager,
            'status' => MusicMembershipStatus::Accepted,
            'invited_by_user_id' => $owner->id,
        ]);

        $request = app(SearchRequestService::class)->createUsingActorContext(
            $manager,
            SearchGoal::FindOrganizerForPerformer,
            [],
            Peformer::class,
            $peformer->id,
        );

        $this->assertSame($peformer->id, (int) $request->initiator_id);
        $this->assertSame(Peformer::class, $request->initiator_type);
    }

    public function test_manager_can_create_request_from_own_profile_context(): void
    {
        $manager = User::factory()->create([
            'music_profiles' => ['manager'],
        ]);

        $request = app(SearchRequestService::class)->createUsingActorContext(
            $manager,
            SearchGoal::FindPerformerForOrganizer,
            ['city' => 'Moscow'],
            User::class,
            $manager->id,
        );

        $this->assertSame($manager->id, (int) $request->initiator_id);
        $this->assertSame(User::class, $request->initiator_type);
    }
}
