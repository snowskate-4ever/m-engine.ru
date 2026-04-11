<div class="mx-auto w-full max-w-3xl space-y-6">
    @if ($showHeading)
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('ui.music.discover_title') }}</flux:heading>
            <flux:description>{{ __('ui.music.discover_hint') }}</flux:description>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <flux:field class="min-w-0 flex-1">
            <flux:label>{{ __('ui.music.discover_query') }}</flux:label>
            <flux:input wire:model.live.debounce.400ms="q" type="search" autocomplete="off" :placeholder="__('ui.music.discover_query_placeholder')" />
        </flux:field>

        <flux:field class="sm:w-56">
            <flux:label>{{ __('ui.music.discover_scope') }}</flux:label>
            <select
                wire:model.live="category"
                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
            >
                @foreach (\App\Services\Music\MusicPublicSearchService::categories() as $cat)
                    <option value="{{ $cat }}">{{ __('ui.music.discover_category.' . $cat) }}</option>
                @endforeach
            </select>
        </flux:field>
    </div>

    @if (mb_strlen(trim($q)) > 0 && mb_strlen(trim($q)) < 2)
        <flux:callout variant="secondary">{{ __('ui.music.discover_min_chars') }}</flux:callout>
    @elseif (mb_strlen(trim($q)) >= 2 && $results->isEmpty())
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('ui.music.discover_no_results') }}</p>
    @elseif (mb_strlen(trim($q)) >= 2)
        <ul class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900">
            @foreach ($results as $row)
                <li class="px-4 py-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <a
                                href="{{ $row['url'] }}"
                                @if ($spaNavigate) wire:navigate @endif
                                class="font-medium text-zinc-900 underline-offset-2 hover:underline dark:text-zinc-100"
                            >
                                {{ $row['name'] }}
                            </a>
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ __('ui.music.discover_type.' . $row['type']) }}
                            </p>
                            @if (! empty($row['excerpt']))
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $row['excerpt'] }}</p>
                            @endif
                        </div>
                        <a
                            href="{{ $row['url'] }}"
                            target="_blank"
                            rel="noopener"
                            class="shrink-0 text-xs text-zinc-500 underline-offset-2 hover:underline dark:text-zinc-400"
                        >
                            {{ __('ui.music.discover_open_new_tab') }}
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <flux:callout variant="secondary">{{ __('ui.music.discover_idle') }}</flux:callout>
    @endif
</div>
