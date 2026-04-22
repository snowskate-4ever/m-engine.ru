<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Enums\LegalDocumentVisibility;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use App\Services\LegalDocuments\LegalDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class LegalDocumentsPanel extends Component
{
    use WithFileUploads;

    public string $ownerKind = 'shop';

    public int $ownerId = 0;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?string $notice = null;

    public string $form_document_type = 'other';

    public string $form_title = '';

    public string $form_visibility = 'owner_only';

    public string $form_payload_text = '';

    public string $form_file_path = '';

    public ?TemporaryUploadedFile $form_file_upload = null;

    public string $form_external_url = '';

    public string $form_effective_from = '';

    public string $form_effective_to = '';

    public function mount(string $ownerKind, int $ownerId): void
    {
        if (! in_array($ownerKind, $this->allowedKinds(), true)) {
            abort(404);
        }
        $this->ownerKind = $ownerKind;
        $this->ownerId = $ownerId;
        $this->authorizeOwner($this->resolveOwner());
        $this->resetFormDefaults();
    }

    public function openCreate(): void
    {
        $this->authorizeOwner($this->resolveOwner());
        $this->editingId = null;
        $this->showForm = true;
        $this->notice = null;
        $this->resetFormDefaults();
    }

    public function openEdit(int $documentId): void
    {
        $this->authorizeOwner($this->resolveOwner());
        $document = $this->findOwnedDocument($documentId);
        $this->editingId = $document->id;
        $this->showForm = true;
        $this->notice = null;

        $this->form_document_type = (string) $document->document_type?->value;
        $this->form_title = (string) $document->title;
        $this->form_visibility = (string) $document->visibility?->value;
        $this->form_payload_text = (string) ($document->currentVersion?->payload_json['text'] ?? '');
        $this->form_file_path = (string) ($document->currentVersion?->file_path ?? '');
        $this->form_external_url = (string) ($document->currentVersion?->external_url ?? '');
        $this->form_effective_from = (string) $document->currentVersion?->effective_from?->format('Y-m-d');
        $this->form_effective_to = (string) $document->currentVersion?->effective_to?->format('Y-m-d');
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(LegalDocumentService $service): void
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);
        $validated = $this->validate($this->rules());

        $attributes = [
            'document_type' => $validated['form_document_type'],
            'title' => $validated['form_title'],
            'visibility' => $validated['form_visibility'],
        ];
        $versionPayload = [
            'payload_json' => ['text' => $validated['form_payload_text'] ?: null],
            'file_path' => $this->resolvedFilePathForSave($validated['form_file_path'] ?: null),
            'external_url' => $validated['form_external_url'] ?: null,
            'effective_from' => $validated['form_effective_from'],
            'effective_to' => $validated['form_effective_to'] ?: null,
        ];

        if ($this->editingId === null) {
            $service->createWithVersion($owner, auth()->user(), $attributes, $versionPayload);
            $this->notice = __('ui.legal_documents.actions.create');
        } else {
            $document = $this->findOwnedDocument($this->editingId);
            $service->updateDraft($document, auth()->user(), $attributes);
            $service->createVersion($document, auth()->user(), $versionPayload);
            $this->notice = __('ui.save');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function submitForReview(int $documentId, LegalDocumentService $service): void
    {
        $document = $this->findOwnedDocument($documentId);
        Gate::authorize('submit', $document);
        $service->submit($document, auth()->user());
        $this->notice = __('ui.legal_documents.actions.submit');
    }

    public function archive(int $documentId, LegalDocumentService $service): void
    {
        $document = $this->findOwnedDocument($documentId);
        Gate::authorize('archive', $document);
        $service->archive($document, auth()->user());
        $this->notice = __('ui.legal_documents.actions.archive');
    }

    public function render(): View
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);

        $documents = LegalDocument::query()
            ->with(['currentVersion', 'versions'])
            ->where('owner_type', $owner::class)
            ->where('owner_id', $owner->getKey())
            ->latest()
            ->get();

        return view('livewire.music.legal-documents-panel', [
            'documents' => $documents,
            'typeOptions' => $this->typeOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form_document_type' => ['required', 'string', Rule::in(array_keys($this->typeOptions()))],
            'form_title' => ['required', 'string', 'max:255'],
            'form_visibility' => ['required', 'string', Rule::in(array_keys($this->visibilityOptions()))],
            'form_payload_text' => ['nullable', 'string', 'max:15000'],
            'form_file_path' => ['nullable', 'string', 'max:2048'],
            'form_file_upload' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,rtf'],
            'form_external_url' => ['nullable', 'url', 'starts_with:https://'],
            'form_effective_from' => ['required', 'date'],
            'form_effective_to' => ['nullable', 'date', 'after_or_equal:form_effective_from'],
        ];
    }

    private function resolveOwner(): Model
    {
        $class = $this->resolveModelClass();

        return $class::query()->findOrFail($this->ownerId);
    }

    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(): string
    {
        return match ($this->ownerKind) {
            'teacher' => Teacher::class,
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'school' => School::class,
            'record_label' => RecordLabel::class,
            'producer_center' => ProducerCenter::class,
            'shop' => Shop::class,
            default => abort(404),
        };
    }

    private function authorizeOwner(Model $owner): void
    {
        Gate::authorize('update', $owner);
    }

    private function findOwnedDocument(int $id): LegalDocument
    {
        $owner = $this->resolveOwner();

        return LegalDocument::query()
            ->where('owner_type', $owner::class)
            ->where('owner_id', $owner->getKey())
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * @return list<string>
     */
    private function allowedKinds(): array
    {
        return ['teacher', 'studio', 'rehearsal', 'school', 'record_label', 'producer_center', 'shop'];
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $out = [];
        foreach (LegalDocumentType::cases() as $case) {
            $out[$case->value] = __('ui.legal_documents.type.'.$case->value);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function visibilityOptions(): array
    {
        return [
            LegalDocumentVisibility::OwnerOnly->value => __('ui.legal_documents.visibility.owner_only'),
            LegalDocumentVisibility::Public->value => __('ui.legal_documents.visibility.public'),
        ];
    }

    private function resetFormDefaults(): void
    {
        $this->form_document_type = LegalDocumentType::Other->value;
        $this->form_title = '';
        $this->form_visibility = LegalDocumentVisibility::OwnerOnly->value;
        $this->form_payload_text = '';
        $this->form_file_path = '';
        $this->form_file_upload = null;
        $this->form_external_url = '';
        $this->form_effective_from = now()->toDateString();
        $this->form_effective_to = '';
    }

    private function resolvedFilePathForSave(?string $currentPath): ?string
    {
        if ($this->form_file_upload instanceof TemporaryUploadedFile) {
            return $this->form_file_upload->store('legal-documents', 'local');
        }

        return $currentPath;
    }
}
