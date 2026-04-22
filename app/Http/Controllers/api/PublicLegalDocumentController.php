<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentVisibility;
use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Support\LegalDocuments\LegalDocumentOwnerMap;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicLegalDocumentController extends Controller
{
    public function index(string $entityType, string $slug): JsonResponse
    {
        $class = LegalDocumentOwnerMap::classByAlias($entityType);
        if (! is_string($class)) {
            throw new NotFoundHttpException('Entity type not supported.');
        }

        $owner = $class::query()
            ->where('slug', $slug)
            ->where('public_page_enabled', true)
            ->firstOrFail();

        $items = LegalDocument::query()
            ->with('currentVersion')
            ->where('owner_type', $class)
            ->where('owner_id', $owner->getKey())
            ->where('status', LegalDocumentStatus::Approved->value)
            ->where('visibility', LegalDocumentVisibility::Public->value)
            ->latest()
            ->get();

        return response()->json([
            'data' => $items->map(function (LegalDocument $item): array {
                return [
                    'id' => $item->id,
                    'document_type' => $item->document_type?->value,
                    'title' => $item->title,
                    'effective_from' => $item->currentVersion?->effective_from?->toIso8601String(),
                    'effective_to' => $item->currentVersion?->effective_to?->toIso8601String(),
                    'file_path' => $item->currentVersion?->file_path,
                    'external_url' => $item->currentVersion?->external_url,
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ];
            }),
        ]);
    }
}
