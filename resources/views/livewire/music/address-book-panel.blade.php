<div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('ui.address.section_title') }}</flux:heading>
            <flux:description>{{ __('ui.address.section_hint') }}</flux:description>
        </div>
        @if (! $showForm)
            <flux:button type="button" variant="primary" wire:click="openCreate">{{ __('ui.address.add') }}</flux:button>
        @endif
    </div>

    @if ($notice)
        <flux:callout variant="success">{{ $notice }}</flux:callout>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
            <flux:heading size="md">{{ $editingId ? __('ui.address.edit') : __('ui.address.new') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('ui.address.label_name') }}</flux:label>
                    <flux:input wire:model="form_name" type="text" :placeholder="__('ui.optional')" />
                    <flux:error name="form_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.address_type') }}</flux:label>
                    <select
                        wire:model="form_address_type"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        @foreach ($addressTypes as $value => $_)
                            <option value="{{ $value }}">{{ __('ui.address.type.' . $value) }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_address_type" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.country') }}</flux:label>
                    <select
                        wire:model.live="form_country_id"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option value="">{{ __('ui.select') }}</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_country_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.region') }}</flux:label>
                    <select
                        wire:model.live="form_region_id"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option value="">{{ __('ui.optional') }}</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_region_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.city') }}</flux:label>
                    <select
                        wire:model="form_city_id"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option value="">{{ __('ui.optional') }}</option>
                        @foreach ($cities as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="form_city_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.street') }}</flux:label>
                    <flux:input wire:model="form_street" type="text" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.house') }}</flux:label>
                    <flux:input wire:model="form_house" type="text" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.building') }}</flux:label>
                    <flux:input wire:model="form_building" type="text" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.apartment') }}</flux:label>
                    <flux:input wire:model="form_apartment" type="text" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('ui.address.postal_code') }}</flux:label>
                    <flux:input wire:model="form_postal_code" type="text" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('ui.address.additional_info') }}</flux:label>
                    <flux:textarea wire:model="form_additional_info" rows="2" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="form_is_primary" :label="__('ui.address.is_primary')" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="form_is_public" :label="__('ui.address.is_public')" />
                </flux:field>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
                <flux:button type="button" variant="ghost" wire:click="cancelForm">{{ __('ui.cancel') }}</flux:button>
            </div>
        </form>
    @endif

    @if ($addresses->isEmpty() && ! $showForm)
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.address.empty') }}</p>
    @elseif ($addresses->isNotEmpty())
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($addresses as $addr)
                <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 text-sm">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">
                            @if (filled($addr->name))
                                {{ $addr->name }}
                            @else
                                {{ __('ui.address.type.' . $addr->address_type) }}
                            @endif
                            @if ($addr->is_primary)
                                <span class="ml-2 rounded bg-zinc-200 px-1.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ __('ui.address.badge_primary') }}</span>
                            @endif
                            @if (! $addr->is_public)
                                <span class="ml-2 text-xs text-zinc-500">{{ __('ui.address.badge_private') }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $addr->full_address }}</p>
                        @if (filled($addr->additional_info))
                            <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($addr->additional_info, 200) }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if (! $addr->is_primary)
                            <flux:button type="button" size="sm" variant="ghost" wire:click="makePrimary({{ $addr->id }})">{{ __('ui.address.make_primary') }}</flux:button>
                        @endif
                        <flux:button type="button" size="sm" variant="ghost" wire:click="openEdit({{ $addr->id }})">{{ __('ui.edit') }}</flux:button>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="deleteAddress({{ $addr->id }})" wire:confirm="{{ __('ui.address.delete_confirm') }}">{{ __('ui.delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
