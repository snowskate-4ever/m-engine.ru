<x-layouts.second_level_layout :title="__('ui.dashboard')" :buttons="$buttons">
    <div class="w-full">
        @php
            $baseline = $data['baseline_metrics'] ?? [];
            $matching = $baseline['matching'] ?? [];
            $integration = $baseline['integration'] ?? [];
            $ai = $baseline['ai'] ?? [];
            $mobile = $baseline['mobile'] ?? [];
            $observability = $baseline['observability'] ?? [];
            $overview = $baseline['overview'] ?? [];
            $mobileChannels = is_array($mobile['channels'] ?? null) ? $mobile['channels'] : [];
            $matchingDaily = is_array($matching['daily'] ?? null) ? $matching['daily'] : [];
            $integrationDaily = is_array($integration['daily'] ?? null) ? $integration['daily'] : [];
            $familyTotals = is_array($overview['family_totals'] ?? null) ? $overview['family_totals'] : [];
            $familyShares = is_array($overview['family_shares'] ?? null) ? $overview['family_shares'] : [];
            $topEvents = is_array($overview['top_events'] ?? null) ? $overview['top_events'] : [];
            $selectedDays = (int) ($baseline['period_days'] ?? 30);
            $periodOptions = [7, 30, 90];
            $maxMatchingDaily = max(1, ...array_map(static fn ($point) => (int) ($point['total'] ?? 0), $matchingDaily ?: [['total' => 0]]));
            $maxIntegrationDaily = max(1, ...array_map(static fn ($point) => (int) ($point['total'] ?? 0), $integrationDaily ?: [['total' => 0]]));
            $maxTopEvents = max(1, ...array_map(static fn ($point) => (int) ($point['total'] ?? 0), $topEvents ?: [['total' => 0]]));
            $familyColors = [
                'matching' => 'bg-blue-500',
                'integration' => 'bg-violet-500',
                'ai' => 'bg-emerald-500',
                'mobile' => 'bg-amber-500',
            ];
        @endphp
        @if(!empty($baseline))
        <section class="mt-6 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.title') }}</h2>
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <label for="dashboard-days" class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.dashboard_metrics.period') }}</label>
                    <select id="dashboard-days" name="days" class="rounded-md border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" onchange="this.form.submit()">
                        @foreach($periodOptions as $option)
                            <option value="{{ $option }}" @selected($selectedDays === $option)>
                                {{ __('ui.dashboard_metrics.period_days', ['days' => $option]) }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('ui.dashboard_metrics.from') }}: {{ $baseline['from'] ?? '-' }}<br>
                {{ __('ui.dashboard_metrics.to') }}: {{ $baseline['to'] ?? '-' }}
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.kpi_total_events') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($overview['total_events'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.kpi_response_rate') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format((float) ($matching['response_rate'] ?? 0), 1) }}%</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.kpi_sync_requests') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($mobile['sync_manifest_requests'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.kpi_ai_requests') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($ai['support_chat_requests'] ?? 0) + (int) ($ai['moderation_score_requests'] ?? 0) + (int) ($ai['partner_recommend_requests'] ?? 0) }}</div>
                </div>
            </div>

            <div class="mt-5 rounded-lg border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                <div class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.obs_section') }}</div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.obs_notification_failures') }}</div>
                        <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($observability['notification_delivery_failed'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.obs_empty_channels') }}</div>
                        <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($observability['notification_empty_channels'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.obs_queue_failures') }}</div>
                        <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($observability['queue_job_failed'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.obs_slow_api') }}</div>
                        <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ (int) ($observability['slow_api_requests'] ?? 0) }}</div>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.obs_hint') }}</p>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.matching_trend') }}</div>
                    @if($matchingDaily !== [])
                        <div class="flex h-44 items-end gap-1">
                            @foreach($matchingDaily as $point)
                                @php
                                    $value = (int) ($point['total'] ?? 0);
                                    $height = (int) round(($value / $maxMatchingDaily) * 100);
                                @endphp
                                <div class="group relative flex-1 rounded-t bg-blue-500/80 hover:bg-blue-500" style="height: {{ max(3, $height) }}%">
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 rounded bg-zinc-900 px-2 py-1 text-[10px] text-white group-hover:block dark:bg-zinc-100 dark:text-zinc-900">
                                        {{ $point['date'] ?? '-' }}: {{ $value }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.no_data') }}</div>
                    @endif
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.integration_trend') }}</div>
                    @if($integrationDaily !== [])
                        <div class="flex h-44 items-end gap-1">
                            @foreach($integrationDaily as $point)
                                @php
                                    $value = (int) ($point['total'] ?? 0);
                                    $height = (int) round(($value / $maxIntegrationDaily) * 100);
                                @endphp
                                <div class="group relative flex-1 rounded-t bg-violet-500/80 hover:bg-violet-500" style="height: {{ max(3, $height) }}%">
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 rounded bg-zinc-900 px-2 py-1 text-[10px] text-white group-hover:block dark:bg-zinc-100 dark:text-zinc-900">
                                        {{ $point['date'] ?? '-' }}: {{ $value }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.no_data') }}</div>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.family_distribution') }}</div>
                    <div class="space-y-2">
                        @foreach(['matching', 'integration', 'ai', 'mobile'] as $family)
                            @php
                                $share = (float) ($familyShares[$family] ?? 0);
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-300">
                                    <span>{{ __('ui.dashboard_metrics.family_'.$family) }}</span>
                                    <span>{{ number_format($share, 1) }}%</span>
                                </div>
                                <div class="h-2 rounded bg-zinc-100 dark:bg-zinc-700">
                                    <div class="h-2 rounded {{ $familyColors[$family] ?? 'bg-zinc-500' }}" style="width: {{ min(100, max(0, $share)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.mobile_channels') }}</div>
                    @if($mobileChannels !== [])
                        <div class="space-y-2">
                            @php
                                $maxChannels = max(1, ...array_map(static fn ($c) => (int) ($c['total'] ?? 0), $mobileChannels));
                            @endphp
                            @foreach($mobileChannels as $channelStat)
                                @php
                                    $channelTotal = (int) ($channelStat['total'] ?? 0);
                                    $width = (int) round(($channelTotal / $maxChannels) * 100);
                                @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-300">
                                        <span>{{ (string) ($channelStat['channel'] ?? 'unknown') }}</span>
                                        <span>{{ $channelTotal }}</span>
                                    </div>
                                    <div class="h-2 rounded bg-zinc-100 dark:bg-zinc-700">
                                        <div class="h-2 rounded bg-amber-500" style="width: {{ max(4, $width) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.no_data') }}</div>
                    @endif
                </div>
            </div>

            <div class="mt-5 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('ui.dashboard_metrics.top_events') }}</div>
                @if($topEvents !== [])
                    <div class="space-y-2">
                        @foreach($topEvents as $eventStat)
                            @php
                                $eventTotal = (int) ($eventStat['total'] ?? 0);
                                $width = (int) round(($eventTotal / $maxTopEvents) * 100);
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3 text-xs text-zinc-600 dark:text-zinc-300">
                                    <span class="truncate">{{ (string) ($eventStat['name'] ?? '-') }}</span>
                                    <span>{{ $eventTotal }}</span>
                                </div>
                                <div class="h-2 rounded bg-zinc-100 dark:bg-zinc-700">
                                    <div class="h-2 rounded bg-zinc-900 dark:bg-zinc-200" style="width: {{ max(4, $width) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.dashboard_metrics.no_data') }}</div>
                @endif
            </div>
        </section>
        @endif
    </div>
</x-layouts.second_level_layout>