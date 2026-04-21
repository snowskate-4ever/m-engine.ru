<div class="space-y-4">
    @if ($savedKey)
        <flux:callout variant="success">{{ __('ui.music.saved') }}</flux:callout>
    @endif

    @if (empty($rows))
        <p class="text-sm text-zinc-600 dark:text-zinc-300">
            {{ __('ui.music.public_pages_empty') }}
        </p>
    @else
        <div class="space-y-3">
            @foreach ($this->sortedRows() as $row)
                @php
                    $key = $row['key'];
                    $slug = trim((string) ($slugs[$key] ?? ''));
                    $isUserProfileRow = str_starts_with($key, 'user_profile:');
                    $ownerKind = $isUserProfileRow ? 'user' : $row['type'];
                    $rowEnabled = $isUserProfileRow
                        ? (bool) ($profileEnabled[$key] ?? false)
                        : (bool) ($enabled[$key] ?? false);
                @endphp
                <div
                    class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700"
                    x-data="{ linksOpen: false, addressesOpen: false, layoutOpen: false }"
                >
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($isUserProfileRow)
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['label'] }}</p>
                            @else
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['name'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['label'] }}</p>
                            @endif
                        </div>
                        @if ($isUserProfileRow)
                            @if ($profileEnabled[$key] ?? false)
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="primary"
                                    class="shrink-0"
                                    icon="x-mark"
                                    wire:click="toggleUserProfileRow('{{ $key }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleUserProfileRow('{{ $key }}')"
                                    :title="__('ui.music.profile_disable')"
                                    :aria-label="__('ui.music.profile_disable')"
                                />
                            @else
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="primary"
                                    class="shrink-0"
                                    icon="power"
                                    wire:click="toggleUserProfileRow('{{ $key }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleUserProfileRow('{{ $key }}')"
                                    :title="__('ui.music.profile_enable')"
                                    :aria-label="__('ui.music.profile_enable')"
                                />
                            @endif
                        @else
                            <flux:checkbox wire:model.live="enabled.{{ $key }}" :label="__('ui.music.fields.public_enabled')" />
                        @endif
                    </div>

                    @if (! $isUserProfileRow)
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-3">
                            <flux:field class="min-w-0 flex-1">
                                <flux:label>{{ __('ui.music.fields.slug') }}</flux:label>
                                <flux:input wire:model.live.debounce.300ms="slugs.{{ $key }}" type="text" placeholder="my-public-slug" />
                                <flux:error :name="'slugs.'.$key" />
                            </flux:field>
                            <div class="flex shrink-0 flex-wrap items-center gap-2 pb-0.5 sm:pb-1">
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="primary"
                                    square
                                    icon="save-floppy"
                                    wire:click="saveRow('{{ $key }}')"
                                    :title="__('ui.save')"
                                />
                                @if ($slug !== '')
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        :href="route('public.profile.show', ['slug' => $slug])"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        {{ __('ui.music.open_public_page') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-start text-sm font-medium text-zinc-800 hover:bg-zinc-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900/60 dark:text-zinc-100 dark:hover:bg-zinc-800/80"
                                x-bind:aria-expanded="layoutOpen ? 'true' : 'false'"
                                @click="layoutOpen = ! layoutOpen; linksOpen = false; addressesOpen = false"
                            >
                                <span>{{ __('ui.music.layout_draft') }}</span>
                                <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-500 transition-transform dark:text-zinc-400" x-bind:class="{ 'rotate-180': layoutOpen }" />
                            </button>
                            <div x-show="layoutOpen" x-cloak class="pt-1">
                                <flux:field class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <flux:label class="sr-only">{{ __('ui.music.layout_draft') }}</flux:label>
                                    <flux:description>{{ __('ui.music.layout_draft_hint') }}</flux:description>
                                    @php
                                        $catalog = match ($row['type']) {
                                            'musician' => \App\Support\Music\PublicProfileBlocks::musicianCatalog(),
                                            'teacher' => \App\Support\Music\PublicProfileBlocks::teacherCatalog(),
                                            'performer' => \App\Support\Music\PublicProfileBlocks::performerCatalog(),
                                            'shop' => \App\Support\Music\PublicProfileBlocks::shopCatalog(),
                                            default => \App\Support\Music\PublicProfileBlocks::venueCatalog(),
                                        };
                                        $criteriaBlocks = match ($row['type']) {
                                            'musician' => [
                                                'instruments' => __('ui.music.fields.instruments'),
                                                'genres' => __('ui.music.fields.genres'),
                                                'cities' => __('ui.music.fields.work_cities'),
                                                'experience' => __('ui.music.fields.experience_since'),
                                            ],
                                            'teacher' => [
                                                'cities' => __('ui.music.fields.work_cities'),
                                            ],
                                            default => [],
                                        };
                                        $criteriaBlockIds = array_keys($criteriaBlocks);
                                        $enabledBlocks = collect($catalog)->filter(
                                            fn (array $block): bool => (bool) ($layoutBlockEnabled[$key][$block['id']] ?? false)
                                        );
                                        $enabledNonCriteriaBlocks = $enabledBlocks->filter(
                                            fn (array $block): bool => ! in_array($block['id'], $criteriaBlockIds, true)
                                        );
                                    @endphp
                                    <div class="mt-2 space-y-3">
                                        @if ($criteriaBlocks !== [])
                                            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ __('ui.music.criteria_visibility_title') }}
                                                </p>
                                                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ __('ui.music.criteria_visibility_hint') }}
                                                </p>
                                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                    @foreach ($criteriaBlocks as $criteriaBlockId => $criteriaBlockLabel)
                                                        <label class="flex items-center gap-2 rounded-md border border-zinc-200 px-2 py-1.5 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                            <input
                                                                type="checkbox"
                                                                wire:model.live="layoutBlockEnabled.{{ $key }}.{{ $criteriaBlockId }}"
                                                                class="rounded border-zinc-300 text-zinc-900 focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:ring-zinc-400/30"
                                                            />
                                                            <span>{{ $criteriaBlockLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <select
                                                wire:model="selectedLayoutBlockId.{{ $key }}"
                                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                                            >
                                                <option value="">{{ __('ui.select') }}</option>
                                                @foreach ($catalog as $block)
                                                    @continue(in_array($block['id'], $criteriaBlockIds, true))
                                                    <option
                                                        value="{{ $block['id'] }}"
                                                        @disabled((bool) ($layoutBlockEnabled[$key][$block['id']] ?? false))
                                                    >
                                                        {{ __($block['label_key']) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <flux:button type="button" variant="primary" class="shrink-0" wire:click="addLayoutBlock('{{ $key }}')">
                                                {{ __('ui.add') }}
                                            </flux:button>
                                        </div>

                                        @if ($enabledNonCriteriaBlocks->isNotEmpty())
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($enabledNonCriteriaBlocks as $block)
                                                    <button
                                                        type="button"
                                                        wire:click="removeLayoutBlock('{{ $key }}', '{{ $block['id'] }}')"
                                                        class="inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-zinc-100 px-3 py-1 text-sm text-zinc-800 transition hover:bg-zinc-200 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                                                    >
                                                        <span>{{ __($block['label_key']) }}</span>
                                                        <span aria-hidden="true">&times;</span>
                                                        <span class="sr-only">{{ __('ui.delete') }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </flux:field>
                            </div>
                        </div>
                    @endif

                    @if ($rowEnabled)
                        <div class="mt-3 space-y-2">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-start text-sm font-medium text-zinc-800 hover:bg-zinc-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900/60 dark:text-zinc-100 dark:hover:bg-zinc-800/80"
                                x-bind:aria-expanded="linksOpen ? 'true' : 'false'"
                                @click="linksOpen = ! linksOpen; addressesOpen = false; layoutOpen = false"
                            >
                                <span>{{ __('ui.social.section_title') }}</span>
                                <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-500 transition-transform dark:text-zinc-400" x-bind:class="{ 'rotate-180': linksOpen }" />
                            </button>
                            <div x-show="linksOpen" x-cloak class="pt-1">
                                <livewire:music.social-links-panel
                                    :owner-kind="$ownerKind"
                                    :owner-id="$row['id']"
                                    :key="'public-social-'.$key"
                                />
                            </div>

                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-start text-sm font-medium text-zinc-800 hover:bg-zinc-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900/60 dark:text-zinc-100 dark:hover:bg-zinc-800/80"
                                x-bind:aria-expanded="addressesOpen ? 'true' : 'false'"
                                @click="addressesOpen = ! addressesOpen; linksOpen = false; layoutOpen = false"
                            >
                                <span>{{ __('ui.address.section_title') }}</span>
                                <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-500 transition-transform dark:text-zinc-400" x-bind:class="{ 'rotate-180': addressesOpen }" />
                            </button>
                            <div x-show="addressesOpen" x-cloak class="pt-1">
                                <livewire:music.address-book-panel
                                    :owner-kind="$ownerKind"
                                    :owner-id="$row['id']"
                                    :key="'public-address-'.$key"
                                />
                            </div>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @endif
</div>
