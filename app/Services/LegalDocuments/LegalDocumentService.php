<?php

declare(strict_types=1);

namespace App\Services\LegalDocuments;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentVisibility;
use App\Models\LegalDocument;
use App\Models\LegalDocumentAudit;
use App\Models\LegalDocumentVersion;
use App\Models\User;

final class LegalDocumentService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $versionPayload
     */
    public function createWithVersion(object $owner, User $actor, array $attributes, array $versionPayload): LegalDocument
    {
        $document = new LegalDocument([
            'document_type' => $attributes['document_type'],
            'title' => $attributes['title'],
            'status' => LegalDocumentStatus::Draft,
            'visibility' => $attributes['visibility'] ?? LegalDocumentVisibility::OwnerOnly,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $document->owner()->associate($owner);
        $document->save();

        $version = $this->createVersion($document, $actor, $versionPayload);
        $document->forceFill(['current_version_id' => $version->id])->save();
        $this->audit($document, 'created', [], [
            'document_type' => $document->document_type?->value,
            'status' => $document->status?->value,
            'visibility' => $document->visibility?->value,
        ], $actor->id);

        return $document->fresh(['currentVersion', 'versions']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDraft(LegalDocument $document, User $actor, array $attributes): LegalDocument
    {
        $old = $this->snapshot($document);
        $document->fill([
            'title' => $attributes['title'] ?? $document->title,
            'visibility' => $attributes['visibility'] ?? $document->visibility,
            'updated_by' => $actor->id,
        ]);
        $document->save();
        $this->audit($document, 'updated', $old, $this->snapshot($document), $actor->id);

        return $document->fresh(['currentVersion', 'versions']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createVersion(LegalDocument $document, User $actor, array $payload): LegalDocumentVersion
    {
        $currentMax = (int) $document->versions()->max('version');
        $version = $document->versions()->create([
            'version' => $currentMax + 1,
            'payload_json' => $payload['payload_json'] ?? null,
            'file_path' => $payload['file_path'] ?? null,
            'external_url' => $payload['external_url'] ?? null,
            'checksum' => $payload['checksum'] ?? null,
            'effective_from' => $payload['effective_from'],
            'effective_to' => $payload['effective_to'] ?? null,
            'published_by' => $actor->id,
            'published_at' => now(),
        ]);

        $document->forceFill([
            'current_version_id' => $version->id,
            'updated_by' => $actor->id,
        ])->save();
        $this->audit($document, 'published', [], [
            'version' => $version->version,
            'effective_from' => $version->effective_from?->toIso8601String(),
        ], $actor->id);

        return $version;
    }

    public function submit(LegalDocument $document, User $actor): LegalDocument
    {
        $old = $this->snapshot($document);
        $document->forceFill([
            'status' => LegalDocumentStatus::PendingReview,
            'updated_by' => $actor->id,
            'rejection_reason' => null,
        ])->save();
        $this->audit($document, 'submitted', $old, $this->snapshot($document), $actor->id);

        return $document->fresh(['currentVersion', 'versions']);
    }

    public function approve(LegalDocument $document, User $actor): LegalDocument
    {
        $old = $this->snapshot($document);
        $document->forceFill([
            'status' => LegalDocumentStatus::Approved,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
            'updated_by' => $actor->id,
        ])->save();
        $this->audit($document, 'approved', $old, $this->snapshot($document), $actor->id);

        return $document->fresh(['currentVersion', 'versions']);
    }

    public function reject(LegalDocument $document, User $actor, string $reason): LegalDocument
    {
        $old = $this->snapshot($document);
        $document->forceFill([
            'status' => LegalDocumentStatus::Rejected,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'updated_by' => $actor->id,
        ])->save();
        $this->audit($document, 'rejected', $old, $this->snapshot($document), $actor->id);

        return $document->fresh(['currentVersion', 'versions']);
    }

    public function archive(LegalDocument $document, User $actor): LegalDocument
    {
        $old = $this->snapshot($document);
        $document->forceFill([
            'status' => LegalDocumentStatus::Archived,
            'updated_by' => $actor->id,
        ])->save();
        $this->audit($document, 'archived', $old, $this->snapshot($document), $actor->id);

        return $document->fresh(['currentVersion', 'versions']);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(LegalDocument $document): array
    {
        return [
            'title' => $document->title,
            'status' => $document->status?->value,
            'visibility' => $document->visibility?->value,
            'reviewed_by' => $document->reviewed_by,
            'reviewed_at' => $document->reviewed_at?->toIso8601String(),
            'rejection_reason' => $document->rejection_reason,
            'current_version_id' => $document->current_version_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function audit(LegalDocument $document, string $action, array $oldValues, array $newValues, ?int $actorId): void
    {
        LegalDocumentAudit::query()->create([
            'legal_document_id' => $document->id,
            'action' => $action,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'performed_by' => $actorId,
            'performed_at' => now(),
        ]);
    }
}
