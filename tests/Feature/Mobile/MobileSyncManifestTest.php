<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Enums\AdStatus;
use App\Enums\SearchGoal;
use App\Enums\SearchRequestStatus;
use App\Models\SearchRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MobileSyncManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_includes_draft_search_requests(): void
    {
        $user = User::factory()->create();
        SearchRequest::query()->create([
            'search_goal' => SearchGoal::FindMusicianForPerformer,
            'status' => SearchRequestStatus::Draft,
            'initiator_type' => User::class,
            'initiator_id' => $user->id,
            'created_by_user_id' => $user->id,
            'ad_status' => AdStatus::Draft,
            'criteria' => null,
        ]);

        Sanctum::actingAs($user);
        $r = $this->getJson('/api/mobile/v1/sync/manifest');
        $r->assertOk();
        $items = $r->json('collections.0.items');
        $this->assertCount(1, (array) $items);
    }
}
