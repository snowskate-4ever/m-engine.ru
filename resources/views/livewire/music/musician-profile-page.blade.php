<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

        @if ($enabled)
        <div x-data="{ layoutSettingsOpen: false }">
        <form wire:submit="save" class="space-y-8">
    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        @unless ($embeddedInProfilesHub)
            <flux:heading size="lg">{{ __('ui.music.musician_card') }}</flux:heading>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:description>{{ __('ui.music.profile_musician_hint') }}</flux:description>
            </div>
        @else
            <flux:heading size="lg">{{ __('ui.music.musician_hub_editor_title') }}</flux:heading>
            <div class="flex flex-col gap-2">
                <flux:description>{{ __('ui.music.musician_hub_editor_hint') }}</flux:description>
                <flux:link :href="route('music.musician')" wire:navigate class="text-sm font-medium">
                    {{ __('ui.music.musician_hub_full_page_link') }}
                </flux:link>
            </div>
        @endunless

            <div class="space-y-4">
            <flux:field>
                <flux:label>{{ __('ui.music.fields.name') }}</flux:label>
                <flux:input wire:model="name" type="text" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.description') }}</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            </div>
    </div>

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.musician_criteria_section') }}</flux:heading>
        <flux:description>{{ __('ui.music.musician_criteria_hint') }}</flux:description>

            <div class="space-y-4">
            <flux:field>
                <flux:label>{{ __('ui.music.fields.instruments') }}</flux:label>
                <div class="mt-2 space-y-3">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <select
                            wire:model="selectedInstrumentId"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">{{ __('ui.select') }}</option>
                            @foreach ($instruments as $instrument)
                                <option value="{{ $instrument->id }}" @disabled(in_array($instrument->id, $instrumentIds, true))>
                                    {{ $instrument->name }}
                                </option>
                            @endforeach
                        </select>
                        <flux:button type="button" wire:click="addInstrument" variant="primary" class="shrink-0">
                            {{ __('ui.add') }}
                        </flux:button>
                    </div>

                    @if (! empty($instrumentIds))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($instruments->whereIn('id', $instrumentIds)->sortBy('name') as $instrument)
                                <button
                                    type="button"
                                    wire:click="removeInstrument({{ $instrument->id }})"
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
                <flux:error name="instrumentIds" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.genres') }}</flux:label>
                <div class="mt-2 space-y-3">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <select
                            wire:model="selectedGenreId"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">{{ __('ui.select') }}</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" @disabled(in_array($genre->id, $genreIds, true))>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                        <flux:button type="button" wire:click="addGenre" variant="primary" class="shrink-0">
                            {{ __('ui.add') }}
                        </flux:button>
                    </div>

                    @if (! empty($genreIds))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($genres->whereIn('id', $genreIds)->sortBy('name') as $genre)
                                <button
                                    type="button"
                                    wire:click="removeGenre({{ $genre->id }})"
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
                <flux:error name="genreIds" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.work_cities') }}</flux:label>
                <flux:description>{{ __('ui.music.fields.city_picker_hint') }}</flux:description>
                <div class="mt-2 space-y-3">
                    <div>
                        <span class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('ui.music.fields.city_country') }}</span>
                        @if ($countries->isEmpty())
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.fields.city_country_empty') }}</p>
                        @else
                        <select
                            wire:model.live="cityPickerCountryId"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <select
                            wire:model="selectedCityId"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">{{ __('ui.select') }}</option>
                            @foreach ($cityPickerCities as $city)
                                <option value="{{ $city->id }}" @disabled(in_array($city->id, $cityIds, true))>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                        <flux:button type="button" wire:click="addCity" variant="primary" class="shrink-0">
                            {{ __('ui.add') }}
                        </flux:button>
                    </div>

                    @if (! empty($cityIds))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($pickedCities as $city)
                                <button
                                    type="button"
                                    wire:click="removeCity({{ $city->id }})"
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
                <flux:error name="cityIds" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.experience_since') }}</flux:label>
                <flux:description>{{ __('ui.music.fields.experience_since_hint') }}</flux:description>
                <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <span class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.music.fields.experience_start_month') }}</span>
                        <select
                            wire:model="experienceStartMonth"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">{{ __('ui.select') }}</option>
                            @foreach ($experienceMonthOptions as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.music.fields.experience_start_year') }}</span>
                        <select
                            wire:model="experienceStartYear"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">{{ __('ui.select') }}</option>
                            @foreach ($experienceYearOptions as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <flux:error name="experienceStartMonth" />
                <flux:error name="experienceStartYear" />
            </flux:field>

            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button type="submit" variant="primary">{{ __('ui.save') }}</flux:button>
                <flux:button type="button" variant="ghost" @click="layoutSettingsOpen = true">{{ __('ui.music.layout_draft') }}</flux:button>
                @if ($record)
                    <flux:button type="button" wire:click="publishLayout" variant="filled">{{ __('ui.music.publish_layout') }}</flux:button>
                @endif
            </div>
            </div>
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
        @elseif (! $embeddedInProfilesHub)
        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('ui.music.musician_card') }}</flux:heading>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:description>{{ __('ui.music.musician_page_enable_hint') }}</flux:description>
            </div>
        </div>
        @endif

    @if ($record && ! $embeddedInProfilesHub)
        <div id="music-musician-lineup" class="scroll-mt-24 space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('ui.music.lineup_musician_section') }}</flux:heading>
            <flux:description>{{ __('ui.music.lineup_musician_section_hint') }}</flux:description>

            @if ($lineupNotice)
                <flux:callout variant="success">{{ $lineupNotice }}</flux:callout>
            @endif
            @if ($lineupError)
                <flux:callout variant="warning">{{ $lineupError }}</flux:callout>
            @endif

            @if ($pendingLineupInvites->isNotEmpty())
                <flux:heading size="md">{{ __('ui.music.lineup_invites_inbox') }}</flux:heading>
                <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($pendingLineupInvites as $p)
                        <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->name }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <flux:button type="button" size="sm" variant="primary" wire:click="acceptLineupInvite({{ $p->id }})">
                                    {{ __('ui.music.lineup_accept') }}
                                </flux:button>
                                <flux:button type="button" size="sm" variant="ghost" wire:click="declineLineupInvite({{ $p->id }})">
                                    {{ __('ui.music.lineup_decline') }}
                                </flux:button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($acceptedLineup->isNotEmpty())
                <flux:heading size="md">{{ __('ui.music.lineup_my_bands') }}</flux:heading>
                <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($acceptedLineup as $p)
                        <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->name }}</p>
                                <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <input
                                        type="checkbox"
                                        class="rounded border-zinc-300 text-zinc-900 focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:ring-zinc-400/30"
                                        @checked($p->pivot->show_on_musician_profile)
                                        wire:change="setLineupShowOnProfile($event.target.checked, {{ $p->id }})"
                                    />
                                    <span>{{ __('ui.music.lineup_show_on_profile') }}</span>
                                </label>
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="leaveLineup({{ $p->id }})" wire:confirm="{{ __('ui.music.lineup_leave_confirm') }}">
                                {{ __('ui.music.lineup_leave') }}
                            </flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($pendingLineupInvites->isEmpty() && $acceptedLineup->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.lineup_musician_empty') }}</p>
            @endif
        </div>
    @endif
</div>
