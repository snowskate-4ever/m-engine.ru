<div class="mx-auto w-full max-w-5xl space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <flux:modal wire:model="showCreateModal" name="search-requests-create" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto">
        <flux:heading size="lg">{{ __('ui.music.search_requests_create_modal_title') }}</flux:heading>
        <div class="mt-4 grid gap-4">
            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_initiator_label') }}</flux:label>
                <select wire:model.live="initiatorRef" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="">{{ __('ui.select') }}</option>
                    @foreach ($initiatorOptions as $actor)
                        <option value="{{ $actor['value'] }}">{{ $actor['label'] }}</option>
                    @endforeach
                </select>
                @error('initiatorRef')
                    <div class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</div>
                @enderror
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_goal_label') }}</flux:label>
                <select wire:model="searchGoal" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    @foreach ($createGoalOptions as $goal)
                        <option value="{{ $goal->value }}">{{ $this->goalTargetLabel($goal) }}</option>
                    @endforeach
                </select>
                <flux:description>{{ __('ui.music.search_requests_goal_hint') }}</flux:description>
            </flux:field>

            <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('ui.music.search_requests_criteria_label') }}</flux:heading>
                <flux:description>{{ __('ui.music.search_requests_criteria_hint') }}</flux:description>

                @foreach ($criteriaFieldOptions as $field)
                    <div wire:key="search-criteria-{{ $field['key'] ?? $loop->index }}">
                    <flux:field>
                        @if (($field['type'] ?? '') !== 'city_multi')
                            <flux:label>{{ $field['label'] }}</flux:label>
                        @endif

                        @if (($field['type'] ?? '') === 'select')
                            <select wire:model="criteriaValues.{{ $field['key'] }}" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                                <option value="">{{ __('ui.select') }}</option>
                                @foreach (($field['options'] ?? []) as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        @elseif (($field['type'] ?? '') === 'date')
                            <input type="date" wire:model="criteriaValues.{{ $field['key'] }}" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                        @elseif (($field['type'] ?? '') === 'integer')
                            <flux:input type="number" wire:model="criteriaValues.{{ $field['key'] }}" min="0" max="80" />
                        @elseif (($field['type'] ?? '') === 'number')
                            <flux:input type="number" wire:model="criteriaValues.{{ $field['key'] }}" />
                        @elseif (($field['type'] ?? '') === 'catalog_multi' && ($field['key'] ?? '') === 'instruments')
                            <div class="mt-2 space-y-3">
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <select wire:model="criteriaPickerInstrumentId" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                                        <option value="">{{ __('ui.select') }}</option>
                                        @foreach ($criteriaInstruments as $instrument)
                                            <option value="{{ $instrument->id }}" @disabled(in_array($instrument->id, $criteriaValues['instruments'] ?? [], true))>
                                                {{ $instrument->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <flux:button type="button" wire:click="addCriteriaInstrument" variant="primary" class="shrink-0">
                                        {{ __('ui.add') }}
                                    </flux:button>
                                </div>
                                @if (! empty($criteriaValues['instruments'] ?? []))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($criteriaInstruments->whereIn('id', $criteriaValues['instruments'] ?? [])->sortBy('name') as $instrument)
                                            <button
                                                type="button"
                                                wire:click="removeCriteriaInstrument({{ $instrument->id }})"
                                                class="inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-zinc-100 px-3 py-1 text-sm text-zinc-800 transition hover:bg-zinc-200 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                                            >
                                                <span>{{ $instrument->name }}</span>
                                                <span aria-hidden="true">&times;</span>
                                                <span class="sr-only">{{ __('ui.delete') }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @elseif (($field['type'] ?? '') === 'catalog_multi' && ($field['key'] ?? '') === 'genres')
                            <div class="mt-2 space-y-3">
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <select wire:model="criteriaPickerGenreId" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                                        <option value="">{{ __('ui.select') }}</option>
                                        @foreach ($criteriaGenres as $genre)
                                            <option value="{{ $genre->id }}" @disabled(in_array($genre->id, $criteriaValues['genres'] ?? [], true))>
                                                {{ $genre->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <flux:button type="button" wire:click="addCriteriaGenre" variant="primary" class="shrink-0">
                                        {{ __('ui.add') }}
                                    </flux:button>
                                </div>
                                @if (! empty($criteriaValues['genres'] ?? []))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($criteriaGenres->whereIn('id', $criteriaValues['genres'] ?? [])->sortBy('name') as $genre)
                                            <button
                                                type="button"
                                                wire:click="removeCriteriaGenre({{ $genre->id }})"
                                                class="inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-zinc-100 px-3 py-1 text-sm text-zinc-800 transition hover:bg-zinc-200 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                                            >
                                                <span>{{ $genre->name }}</span>
                                                <span aria-hidden="true">&times;</span>
                                                <span class="sr-only">{{ __('ui.delete') }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @elseif (($field['type'] ?? '') === 'city_multi')
                            <flux:label>{{ $field['label'] }}</flux:label>
                            <flux:description>{{ __('ui.music.fields.city_picker_hint') }}</flux:description>
                            <div class="mt-2 space-y-3">
                                <div>
                                    <span class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('ui.music.fields.city_country') }}</span>
                                    @if ($criteriaCountries->isEmpty())
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.fields.city_country_empty') }}</p>
                                    @else
                                        <select
                                            wire:model.live="criteriaCityPickerCountryId"
                                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                                        >
                                            @foreach ($criteriaCountries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <select wire:model="criteriaPickerCityId" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                                        <option value="">{{ __('ui.select') }}</option>
                                        @foreach ($criteriaCityPickerCities as $city)
                                            <option value="{{ $city->id }}" @disabled(in_array($city->id, $criteriaValues['cities'] ?? [], true))>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <flux:button type="button" wire:click="addCriteriaCity" variant="primary" class="shrink-0">
                                        {{ __('ui.add') }}
                                    </flux:button>
                                </div>
                                @if (! empty($criteriaValues['cities'] ?? []))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($criteriaPickedCities as $city)
                                            <button
                                                type="button"
                                                wire:click="removeCriteriaCity({{ $city->id }})"
                                                class="inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-zinc-100 px-3 py-1 text-sm text-zinc-800 transition hover:bg-zinc-200 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                                            >
                                                <span>{{ $city->name }}</span>
                                                <span aria-hidden="true">&times;</span>
                                                <span class="sr-only">{{ __('ui.delete') }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <flux:input type="text" wire:model="criteriaValues.{{ $field['key'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" />
                        @endif
                    </flux:field>
                    </div>
                @endforeach

                <flux:error name="criteriaJson" />
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" wire:click="closeCreateModal">
                {{ __('ui.cancel') }}
            </flux:button>
            <flux:button type="button" wire:click="createRequest" variant="primary">
                {{ __('ui.music.search_requests_create_btn') }}
            </flux:button>
        </div>
    </flux:modal>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-3">
            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_filter_status') }}</flux:label>
                <select wire:model.live="statusFilter" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="all">{{ __('ui.music.search_requests_filter_all') }}</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ $this->statusLabel($status) }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_filter_goal') }}</flux:label>
                <select wire:model.live="goalFilter" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="all">{{ __('ui.music.search_requests_filter_all') }}</option>
                    @foreach ($searchGoalOptions as $goal)
                        <option value="{{ $goal->value }}">{{ $this->goalLabel($goal) }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_filter_initiator') }}</flux:label>
                <select wire:model.live="initiatorFilter" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="all">{{ __('ui.music.search_requests_filter_all') }}</option>
                    @foreach ($entityOptions as $actor)
                        <option value="{{ $actor['type'] }}:{{ $actor['id'] }}">{{ $actor['label'] }}</option>
                    @endforeach
                </select>
            </flux:field>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_goal') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_initiator') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_dates') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($requests as $request)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                <div>{{ $this->goalLabel($request->search_goal) }}</div>
                                @if (!empty($request->criteria))
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ json_encode($request->criteria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $this->statusLabel($request->status) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $this->initiatorLabel($request) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                <div>{{ __('ui.music.search_requests_submitted_at') }}: {{ optional($request->submitted_at)->format('Y-m-d H:i') }}</div>
                                <div>{{ __('ui.music.search_requests_expires_at_short') }}: {{ optional($request->expires_at)->format('Y-m-d H:i') ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if (in_array($request->status->value, ['open', 'awaiting_approval'], true))
                                    <flux:button size="xs" variant="danger" wire:click="cancelRequest({{ $request->id }})">
                                        {{ __('ui.music.search_requests_cancel_btn') }}
                                    </flux:button>
                                @elseif (in_array($request->status->value, ['cancelled', 'expired'], true))
                                    <flux:button size="xs" wire:click="reopenRequest({{ $request->id }})">
                                        {{ __('ui.music.search_requests_reopen_btn') }}
                                    </flux:button>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('ui.music.search_requests_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
