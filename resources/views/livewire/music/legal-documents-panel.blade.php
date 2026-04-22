<div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('ui.legal_documents.title') }}</flux:heading>
            <flux:description>{{ __('ui.legal_documents.section_hint') }}</flux:description>
        </div>
        @if (! $showForm)
            <flux:button type="button" variant="primary" square icon="plus" :title="__('ui.legal_documents.actions.create')" wire:click="openCreate" />
        @endif
    </div>

    @if ($notice)
        <flux:callout variant="success">{{ $notice }}</flux:callout>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
            <flux:heading size="md">{{ $editingId ? __('ui.edit') : __('ui.legal_documents.actions.create') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('ui.legal_documents.field_type') }}</flux:label>
                    <select wire:model="form_document_type" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_document_type" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.legal_documents.field_visibility') }}</flux:label>
                    <select wire:model="form_visibility" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                        @foreach($visibilityOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_visibility" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('ui.legal_documents.field_title') }}</flux:label>
                <flux:input wire:model="form_title" type="text" />
                <flux:error name="form_title" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.legal_documents.field_text') }}</flux:label>
                <flux:textarea wire:model="form_payload_text" rows="4" />
                <flux:error name="form_payload_text" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('ui.legal_documents.field_file_upload') }}</flux:label>
                    <input
                        wire:model="form_file_upload"
                        type="file"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    />
                    <flux:error name="form_file_upload" />
                    @if(filled($form_file_path))
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.legal_documents.file_attached') }}</p>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.legal_documents.field_file_path') }}</flux:label>
                    <flux:input wire:model="form_file_path" type="text" readonly />
                    <flux:error name="form_file_path" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('ui.legal_documents.field_external_url') }}</flux:label>
                <flux:input wire:model="form_external_url" type="url" placeholder="https://..." />
                <flux:error name="form_external_url" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('ui.legal_documents.field_effective_from') }}</flux:label>
                    <flux:input wire:model="form_effective_from" type="date" />
                    <flux:error name="form_effective_from" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('ui.legal_documents.field_effective_to') }}</flux:label>
                    <flux:input wire:model="form_effective_to" type="date" />
                    <flux:error name="form_effective_to" />
                </flux:field>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
                <flux:button type="button" variant="ghost" wire:click="cancelForm" square icon="cancel-play" :title="__('ui.cancel')" />
            </div>
        </form>
    @endif

    @if ($documents->isEmpty() && ! $showForm)
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.legal_documents.empty') }}</p>
    @elseif($documents->isNotEmpty())
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach($documents as $document)
                <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 text-sm">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $document->title }}</p>
                        <p class="text-zinc-600 dark:text-zinc-400">
                            {{ __('ui.legal_documents.type.'.$document->document_type?->value) }}
                            · {{ __('ui.legal_documents.status.'.$document->status?->value) }}
                            · {{ __('ui.legal_documents.visibility.'.$document->visibility?->value) }}
                        </p>
                        @if($document->currentVersion)
                            <p class="mt-1 text-zinc-500 dark:text-zinc-400">
                                {{ __('ui.legal_documents.version') }}: {{ $document->currentVersion->version }}
                                · {{ __('ui.legal_documents.field_effective_from') }}: {{ optional($document->currentVersion->effective_from)->format('Y-m-d') }}
                            </p>
                        @endif
                        @if(filled($document->rejection_reason))
                            <p class="mt-1 text-rose-600 dark:text-rose-400">
                                {{ __('ui.legal_documents.moderation.rejection_reason_label') }}: {{ $document->rejection_reason }}
                            </p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <flux:button type="button" size="sm" variant="ghost" wire:click="openEdit({{ $document->id }})">{{ __('ui.edit') }}</flux:button>
                        @if($document->status === \App\Enums\LegalDocumentStatus::Draft || $document->status === \App\Enums\LegalDocumentStatus::Rejected)
                            <flux:button type="button" size="sm" variant="ghost" wire:click="submitForReview({{ $document->id }})">{{ __('ui.legal_documents.actions.submit') }}</flux:button>
                        @endif
                        @if($document->status !== \App\Enums\LegalDocumentStatus::Archived)
                            <flux:button type="button" size="sm" variant="ghost" wire:click="archive({{ $document->id }})">{{ __('ui.legal_documents.actions.archive') }}</flux:button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
