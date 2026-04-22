<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentVisibility;
use App\Http\Controllers\Controller;
use App\Models\LegalDocumentVersion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicLegalDocumentDownloadController extends Controller
{
    public function __invoke(LegalDocumentVersion $version): StreamedResponse
    {
        $version->loadMissing('legalDocument.owner');
        $document = $version->legalDocument;
        $owner = $document?->owner;

        if (
            $document === null
            || $owner === null
            || ! $owner->public_page_enabled
            || ($document->status?->value ?? null) !== LegalDocumentStatus::Approved->value
            || ($document->visibility?->value ?? null) !== LegalDocumentVisibility::Public->value
            || ! is_string($version->file_path)
            || $version->file_path === ''
        ) {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($version->file_path)) {
            abort(404);
        }

        $name = basename($version->file_path);

        return $disk->download($version->file_path, $name);
    }
}
