<div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('ui.social.section_title') }}</flux:heading>
            <flux:description>{{ __('ui.social.section_hint') }}</flux:description>
        </div>
        @if (! $showForm)
            <flux:button type="button" variant="primary" wire:click="openCreate">{{ __('ui.social.add') }}</flux:button>
        @endif
    </div>

    @if ($notice)
        <flux:callout variant="success">{{ $notice }}</flux:callout>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
            <flux:heading size="md">{{ $editingId ? __('ui.social.edit') : __('ui.social.new') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('ui.social.url') }}</flux:label>
                <flux:input wire:model="form_link" type="url" placeholder="https://example.com" />
                <flux:error name="form_link" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('ui.social.type') }}</flux:label>
                    <select
                        wire:model="form_type"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option value="">{{ __('ui.optional') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ __('ui.social.types.'.$type) }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_type" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.social.name') }}</flux:label>
                    <flux:input wire:model="form_name" type="text" :placeholder="__('ui.optional')" />
                    <flux:error name="form_name" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('ui.social.description') }}</flux:label>
                <flux:textarea wire:model="form_description" rows="2" :placeholder="__('ui.optional')" />
                <flux:error name="form_description" />
            </flux:field>

            <flux:checkbox wire:model="form_active" :label="__('ui.social.active')" />

            <div class="flex flex-wrap gap-2">
                <flux:button type="submit" variant="primary">{{ __('ui.save') }}</flux:button>
                <flux:button type="button" variant="ghost" wire:click="cancelForm">{{ __('ui.cancel') }}</flux:button>
            </div>
        </form>
    @endif

    @if ($links->isEmpty() && ! $showForm)
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.social.empty') }}</p>
    @elseif ($links->isNotEmpty())
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($links as $link)
                <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $link->name ?: __('ui.social.types.'.($link->type ?: 'other')) }}
                            </p>
                            @if (! $link->active)
                                <span class="rounded bg-zinc-200 px-1.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ __('ui.social.badge_inactive') }}</span>
                            @endif
                        </div>
                        <a href="{{ $link->link }}" target="_blank" rel="noopener noreferrer nofollow" class="mt-1 block break-all text-zinc-700 underline underline-offset-2 dark:text-zinc-300">
                            {{ $link->link }}
                        </a>
                        @if (filled($link->description))
                            <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($link->description, 200) }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <flux:button type="button" size="sm" variant="ghost" wire:click="toggleActive({{ $link->id }})">
                            {{ $link->active ? __('ui.social.deactivate') : __('ui.social.activate') }}
                        </flux:button>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="openEdit({{ $link->id }})">{{ __('ui.edit') }}</flux:button>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="deleteSocial({{ $link->id }})" wire:confirm="{{ __('ui.social.delete_confirm') }}">{{ __('ui.delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
