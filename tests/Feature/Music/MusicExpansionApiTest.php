<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MusicExpansionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_sync_connectors_endpoint_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/music/calendar-sync/connectors')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_calendar_sync_feed_validates_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/music/calendar-sync/feed')
            ->assertStatus(422);
    }

    public function test_activity_feed_personalization_endpoint_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/music/activity-feed?limit=5')
            ->assertOk()
            ->assertJsonStructure(['data' => ['verified_reviews', 'active_search_requests']]);
    }

    public function test_ai_content_assistant_endpoint_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/ai/expansion/compose-content', [
            'content_type' => 'post',
            'brief' => 'Новый музыкальный ивент в эти выходные.',
        ])->assertOk()->assertJsonStructure(['content']);
    }
}
