<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.teacher_card') }}</flux:heading>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:description>{{ __('ui.music.profile_teacher_hint') }}</flux:description>
        </div>

        @if ($enabled)
        <div x-data="{ layoutSettingsOpen: false }">
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
                <flux:checkbox wire:model="available_other_cities" :label="__('ui.music.fields.available_other_cities')" />
            </flux:field>

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

            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
                <flux:button type="button" variant="ghost" @click="layoutSettingsOpen = true">{{ __('ui.music.layout_draft') }}</flux:button>
                @if ($record)
                    <flux:button type="button" wire:click="publishLayout" variant="filled">{{ __('ui.music.publish_layout') }}</flux:button>
                @endif
            </div>
        </form>
        <div
            x-show="layoutSettingsOpen"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[120] flex items-center justify-center bg-zinc-900/50 p-4"
            @click.self="layoutSettingsOpen = false"
            @keydown.escape.window="layoutSettingsOpen = false"
        >
            <div class="w-full max-w-2xl rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('ui.music.layout_draft') }}</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.music.layout_draft_hint') }}</p>
                    </div>
                    <flux:button type="button" size="sm" variant="ghost" @click="layoutSettingsOpen = false">
                        {{ __('ui.close') }}
                    </flux:button>
                </div>
                <div class="flex flex-col gap-2">
                    @foreach ($blockCatalog as $row)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:text-zinc-100">
                            <span>{{ __($row['label_key']) }}</span>
                            <input
                                type="checkbox"
                                wire:model="layoutBlockEnabled.{{ $row['id'] }}"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:ring-zinc-400/30"
                            />
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        </div>
        @endif
    </div>

    @if ($enabled)
        <livewire:music.music-user-json-criteria-form
            wire:key="teacher-profile-criteria"
            :profile-key="\App\Enums\UserMusicProfile::Teacher->value"
            :enabled="true"
        />
    @endif

</div>
