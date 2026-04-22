<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class PublicLegalDocumentsBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shop_page_renders_approved_public_legal_documents(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Doc Shop',
            'owner_user_id' => $owner->id,
            'slug' => 'public-legal-shop-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $document = LegalDocument::query()->create([
            'owner_type' => Shop::class,
            'owner_id' => $shop->id,
            'document_type' => 'public_offer',
            'title' => 'Public Offer v1',
            'status' => 'approved',
            'visibility' => 'public',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $version = LegalDocumentVersion::query()->create([
            'legal_document_id' => $document->id,
            'version' => 1,
            'payload_json' => ['text' => 'offer'],
            'effective_from' => now()->startOfDay(),
            'published_by' => $owner->id,
            'published_at' => now(),
        ]);
        $document->current_version_id = $version->id;
        $document->save();

        $this->get(route('public.shops.show', ['slug' => $shop->slug]))
            ->assertOk()
            ->assertSee('Public Offer v1');
    }

    public function test_public_legal_document_download_requires_valid_signature(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Download Shop',
            'owner_user_id' => $owner->id,
            'slug' => 'download-legal-shop-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $document = LegalDocument::query()->create([
            'owner_type' => Shop::class,
            'owner_id' => $shop->id,
            'document_type' => 'public_offer',
            'title' => 'Public Offer v2',
            'status' => 'approved',
            'visibility' => 'public',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        Storage::disk('local')->put('legal-documents/test-offer.pdf', 'pdf');
        $version = LegalDocumentVersion::query()->create([
            'legal_document_id' => $document->id,
            'version' => 1,
            'file_path' => 'legal-documents/test-offer.pdf',
            'effective_from' => now()->startOfDay(),
            'published_by' => $owner->id,
            'published_at' => now(),
        ]);
        $document->current_version_id = $version->id;
        $document->save();

        $this->get(route('public.legal-document.download', ['version' => $version->id]))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute(
            'public.legal-document.download',
            now()->addMinutes(30),
            ['version' => $version->id],
        );
        $this->get($signedUrl)
            ->assertOk();
    }
}
