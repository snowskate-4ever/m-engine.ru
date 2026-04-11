<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Models\MusicProfileMembership;
use App\Models\Peformer;
use App\Models\User;
use App\Services\Music\MusicActorContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusicActorContextServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_switch_to_delegated_performer_actor(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create([
            'music_profiles' => ['manager'],
        ]);
        $peformer = Peformer::query()->create([
            'name' => 'Managed Band',
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

        app(MusicActorContextService::class)->setActiveActor($manager, Peformer::class, $peformer->id);

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'active_music_actor_type' => Peformer::class,
            'active_music_actor_id' => $peformer->id,
        ]);
    }
}
