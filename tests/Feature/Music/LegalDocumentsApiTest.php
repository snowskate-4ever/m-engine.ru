<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\LegalDocument;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LegalDocumentsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_submit_and_publish_legal_document(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Doc Shop',
            'owner_user_id' => $owner->id,
            'slug' => 'doc-shop-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);
        Sanctum::actingAs($owner);

        $create = $this->postJson("/api/music/legal-documents/shop/{$shop->id}", [
            'document_type' => 'public_offer',
            'title' => 'Оферта',
            'visibility' => 'public',
            'payload_json' => ['text' => 'v1'],
            'effective_from' => now()->toIso8601String(),
        ])->assertCreated();

        $id = (int) $create->json('data.id');

        $this->postJson("/api/music/legal-documents/{$id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $this->assertDatabaseHas('legal_documents', [
            'id' => $id,
            'owner_type' => Shop::class,
            'owner_id' => $shop->id,
            'status' => 'pending_review',
        ]);
    }

    public function test_public_endpoint_returns_only_approved_public_documents(): void
    {
        $owner = User::factory()->create();
        $moderator = User::factory()->create(['email' => 'moderator@example.com']);
        config()->set('legal_documents.moderator_emails', ['moderator@example.com']);

        $studio = Studio::query()->create([
            'name' => 'Legal Studio',
            'owner_user_id' => $owner->id,
            'slug' => 'legal-studio-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        Sanctum::actingAs($owner);
        $response = $this->postJson("/api/music/legal-documents/studio/{$studio->id}", [
            'document_type' => 'privacy_policy',
            'title' => 'Privacy',
            'visibility' => 'public',
            'payload_json' => ['text' => 'policy'],
            'effective_from' => now()->toIso8601String(),
        ])->assertCreated();
        $documentId = (int) $response->json('data.id');

        Sanctum::actingAs($moderator);
        $this->postJson("/api/music/legal-documents/{$documentId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->getJson('/api/public/studio/'.$studio->slug.'/legal-documents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $documentId);
    }

    public function test_non_owner_cannot_access_owner_documents(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Secure Shop',
            'owner_user_id' => $owner->id,
            'slug' => 'secure-shop-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);
        $document = LegalDocument::query()->create([
            'owner_type' => Shop::class,
            'owner_id' => $shop->id,
            'document_type' => 'public_offer',
            'title' => 'Secure doc',
            'status' => 'draft',
            'visibility' => 'owner_only',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        Sanctum::actingAs($other);
        $this->getJson("/api/music/legal-documents/{$document->id}")
            ->assertForbidden();
    }
}
