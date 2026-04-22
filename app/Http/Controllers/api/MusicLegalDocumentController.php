<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Enums\LegalDocumentVisibility;
use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Services\LegalDocuments\LegalDocumentService;
use App\Support\LegalDocuments\LegalDocumentOwnerMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MusicLegalDocumentController extends Controller
{
    public function __construct(
        private readonly LegalDocumentService $service,
    ) {}

    public function index(Request $request, string $ownerType, int $ownerId): JsonResponse
    {
        $owner = $this->resolveOwner($ownerType, $ownerId);
        Gate::authorize('update', $owner);

        $query = LegalDocument::query()
            ->with(['currentVersion', 'versions'])
            ->where('owner_type', $owner::class)
            ->where('owner_id', $owner->getKey())
            ->latest();

        if (is_string($request->query('status'))) {
            $query->where('status', (string) $request->query('status'));
        }
        if (is_string($request->query('document_type'))) {
            $query->where('document_type', (string) $request->query('document_type'));
        }
        if (is_string($request->query('visibility'))) {
            $query->where('visibility', (string) $request->query('visibility'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (LegalDocument $document) => $this->toArray($document)),
        ]);
    }

    public function store(Request $request, string $ownerType, int $ownerId): JsonResponse
    {
        $owner = $this->resolveOwner($ownerType, $ownerId);
        Gate::authorize('update', $owner);

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:'.implode(',', array_map(fn (LegalDocumentType $t) => $t->value, LegalDocumentType::cases()))],
            'title' => ['required', 'string', 'max:255'],
            'visibility' => ['nullable', 'string', 'in:'.implode(',', array_map(fn (LegalDocumentVisibility $v) => $v->value, LegalDocumentVisibility::cases()))],
            'payload_json' => ['nullable', 'array'],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'external_url' => ['nullable', 'url', 'starts_with:https://'],
            'checksum' => ['nullable', 'string', 'max:64'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $document = $this->service->createWithVersion($owner, $request->user(), $validated, $validated);

        return response()->json(['data' => $this->toArray($document)], 201);
    }

    public function show(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('view', $legalDocument);

        return response()->json(['data' => $this->toArray($legalDocument->load(['currentVersion', 'versions']))]);
    }

    public function update(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('update', $legalDocument);
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'visibility' => ['sometimes', 'string', 'in:'.implode(',', array_map(fn (LegalDocumentVisibility $v) => $v->value, LegalDocumentVisibility::cases()))],
        ]);
        $document = $this->service->updateDraft($legalDocument, $request->user(), $validated);

        return response()->json(['data' => $this->toArray($document)]);
    }

    public function createVersion(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('update', $legalDocument);
        $validated = $request->validate([
            'payload_json' => ['nullable', 'array'],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'external_url' => ['nullable', 'url', 'starts_with:https://'],
            'checksum' => ['nullable', 'string', 'max:64'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
        $this->service->createVersion($legalDocument, $request->user(), $validated);

        return response()->json(['data' => $this->toArray($legalDocument->fresh(['currentVersion', 'versions']))]);
    }

    public function submit(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('submit', $legalDocument);
        $document = $this->service->submit($legalDocument, $request->user());

        return response()->json(['data' => $this->toArray($document)]);
    }

    public function archive(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('archive', $legalDocument);
        $document = $this->service->archive($legalDocument, $request->user());

        return response()->json(['data' => $this->toArray($document)]);
    }

    public function approve(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('moderate', $legalDocument);
        $document = $this->service->approve($legalDocument, $request->user());

        return response()->json(['data' => $this->toArray($document)]);
    }

    public function reject(Request $request, LegalDocument $legalDocument): JsonResponse
    {
        Gate::authorize('moderate', $legalDocument);
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);
        $document = $this->service->reject($legalDocument, $request->user(), $validated['rejection_reason']);

        return response()->json(['data' => $this->toArray($document)]);
    }

    private function resolveOwner(string $ownerType, int $ownerId): Model
    {
        $class = LegalDocumentOwnerMap::classByAlias($ownerType);
        if (! is_string($class)) {
            throw new NotFoundHttpException('Owner type not supported.');
        }

        return $class::query()->findOrFail($ownerId);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(LegalDocument $document): array
    {
        $currentVersion = $document->currentVersion;

        return [
            'id' => $document->id,
            'owner_type' => $document->owner_type,
            'owner_id' => $document->owner_id,
            'document_type' => $document->document_type?->value,
            'title' => $document->title,
            'status' => $document->status?->value,
            'visibility' => $document->visibility?->value,
            'reviewed_by' => $document->reviewed_by,
            'reviewed_at' => $document->reviewed_at?->toIso8601String(),
            'rejection_reason' => $document->rejection_reason,
            'current_version' => $currentVersion ? [
                'id' => $currentVersion->id,
                'version' => $currentVersion->version,
                'payload_json' => $currentVersion->payload_json,
                'file_path' => $currentVersion->file_path,
                'external_url' => $currentVersion->external_url,
                'effective_from' => $currentVersion->effective_from?->toIso8601String(),
                'effective_to' => $currentVersion->effective_to?->toIso8601String(),
                'published_at' => $currentVersion->published_at?->toIso8601String(),
            ] : null,
            'versions' => $document->relationLoaded('versions')
                ? $document->versions->map(fn ($version) => [
                    'id' => $version->id,
                    'version' => $version->version,
                    'effective_from' => $version->effective_from?->toIso8601String(),
                    'effective_to' => $version->effective_to?->toIso8601String(),
                    'published_at' => $version->published_at?->toIso8601String(),
                ])->values()
                : [],
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ];
    }
}
