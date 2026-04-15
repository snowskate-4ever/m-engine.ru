<?php

declare(strict_types=1);

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Services\Gamification\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GamificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_xp_and_leaderboard(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $g = app(GamificationService::class);
        $g->addXp($u1, 50, 'test');
        $g->addXp($u2, 10, 'test');

        Sanctum::actingAs($u1);
        $this->getJson('/api/gamification/xp')->assertOk()->assertJson(['total_xp' => 50]);

        $this->getJson('/api/gamification/leaderboard?limit=10')
            ->assertOk()
            ->assertJsonPath('leaderboard.0.user_id', $u1->id);
    }
}
