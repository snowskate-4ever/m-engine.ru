@if ($enabled)
    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.profile_criteria_geo_experience_section') }}</flux:heading>
        <flux:description>{{ __('ui.music.profile_criteria_geo_experience_hint') }}</flux:description>

        <div class="space-y-4">
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
            </div>
        </div>
    </form>
@endif
