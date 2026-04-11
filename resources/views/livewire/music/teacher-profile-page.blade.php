<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.teacher_card') }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('ui.music.fields.name') }}</flux:label>
                <flux:input wire:model="name" type="text" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.description') }}</flux:label>
                <flux:textarea wire:model="description" rows="4" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.slug') }}</flux:label>
                <flux:input wire:model="slug" type="text" />
                <flux:description>{{ __('ui.music.fields.slug_hint') }}</flux:description>
                <flux:error name="slug" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="public_page_enabled" :label="__('ui.music.fields.public_enabled')" />
                <flux:checkbox wire:model="available_other_cities" :label="__('ui.music.fields.available_other_cities')" />
            </flux:field>

            @if ($record && filled($slug))
                <flux:callout variant="secondary" inline>
                    <a href="{{ route('public.teachers.show', ['slug' => $slug]) }}" target="_blank" rel="noopener" class="font-medium underline underline-offset-2">
                        {{ __('ui.music.open_public_page') }}
                    </a>
                </flux:callout>
            @endif

            <flux:separator />

            <flux:heading size="md">{{ __('ui.music.legal_section') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.legal_entity_type') }}</flux:label>
                <select
                    wire:model="legal_entity_type"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="">{{ __('ui.optional') }}</option>
                    <option value="individual">{{ __('ui.music.legal_individual') }}</option>
                    <option value="legal_person">{{ __('ui.music.legal_person') }}</option>
                </select>
                <flux:error name="legal_entity_type" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.company_name') }}</flux:label>
                <flux:input wire:model="company_name" type="text" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.inn') }}</flux:label>
                <flux:input wire:model="inn" type="text" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.ogrn') }}</flux:label>
                <flux:input wire:model="ogrn" type="text" />
            </flux:field>

            <flux:separator />

            <flux:heading size="md">{{ __('ui.music.layout_draft') }}</flux:heading>
            <flux:description>{{ __('ui.music.layout_draft_hint') }}</flux:description>
            <div class="flex flex-col gap-2">
                @foreach ($blockCatalog as $row)
                    <flux:checkbox wire:model="layoutBlockEnabled.{{ $row['id'] }}" :label="__($row['label_key'])" />
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button type="submit" variant="primary">{{ __('ui.save') }}</flux:button>
                @if ($record)
                    <flux:button type="button" wire:click="publishLayout" variant="filled">{{ __('ui.music.publish_layout') }}</flux:button>
                @endif
            </div>
        </form>
    </div>

    @if ($record)
        <livewire:music.social-links-panel owner-kind="teacher" :owner-id="$record->id" :key="'socials-teacher-'.$record->id" />
        <livewire:music.address-book-panel owner-kind="teacher" :owner-id="$record->id" :key="'addresses-teacher-'.$record->id" />
    @endif
</div>
