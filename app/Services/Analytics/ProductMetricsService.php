<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\ProductMetricEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class ProductMetricsService
{
    /**
     * @param  array<string,mixed>  $meta
     */
    public function track(string $eventName, ?int $userId = null, string $channel = 'web', array $meta = []): void
    {
        ProductMetricEvent::query()->create([
            'event_name' => $eventName,
            'user_id' => $userId,
            'channel' => $channel,
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function baselineSnapshot(int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $from = now()->subDays($days);
        $matchingCreated = $this->count('matching.search_request.created', $from);
        $matchingResponded = $this->count('matching.search_request.responded', $from);
        $matchingFeedViews = $this->count('matching.feed.viewed', $from);
        $integrationV1 = $this->countByPrefix('integration.v1.', $from);
        $integrationV2 = $this->countByPrefix('integration.v2.', $from);
        $integrationTokens = $this->count('integration.token.minted', $from);
        $aiSupport = $this->count('ai.support_chat.requested', $from);
        $aiModeration = $this->count('ai.moderation_score.requested', $from);
        $aiPartner = $this->count('ai.partner_recommend.requested', $from);
        $mobileSync = $this->count('mobile.sync_manifest.requested', $from);
        $notificationFailures = $this->count('notification.delivery_failed', $from);
        $notificationEmptyChannels = $this->count('notification.gateway.empty_channels', $from);
        $queueJobFailures = $this->count('observability.queue.job_failed', $from);
        $slowApi = $this->count('observability.http.slow_api', $from);

        $familyTotals = [
            'matching' => $matchingCreated + $matchingResponded + $matchingFeedViews,
            'integration' => $integrationV1 + $integrationV2 + $integrationTokens,
            'ai' => $aiSupport + $aiModeration + $aiPartner,
            'mobile' => $mobileSync,
        ];
        $allFamiliesTotal = array_sum($familyTotals);

        return [
            'period_days' => $days,
            'from' => $from->toIso8601String(),
            'to' => now()->toIso8601String(),
            'matching' => [
                'search_requests_created' => $matchingCreated,
                'search_requests_responded' => $matchingResponded,
                'feed_views' => $matchingFeedViews,
                'daily' => $this->dailySeries([
                    'matching.search_request.created',
                    'matching.search_request.responded',
                    'matching.feed.viewed',
                ], $from, $days),
            ],
            'integration' => [
                'v1_calls' => $integrationV1,
                'v2_calls' => $integrationV2,
                'token_minted' => $integrationTokens,
                'daily' => $this->dailySeriesByPrefix([
                    'integration.v1.',
                    'integration.v2.',
                    'integration.token.',
                ], $from, $days),
            ],
            'ai' => [
                'support_chat_requests' => $aiSupport,
                'moderation_score_requests' => $aiModeration,
                'partner_recommend_requests' => $aiPartner,
            ],
            'mobile' => [
                'sync_manifest_requests' => $mobileSync,
                'channels' => $this->channelsBreakdown($from),
            ],
            'observability' => [
                'notification_delivery_failed' => $notificationFailures,
                'notification_empty_channels' => $notificationEmptyChannels,
                'queue_job_failed' => $queueJobFailures,
                'slow_api_requests' => $slowApi,
            ],
            'overview' => [
                'family_totals' => $familyTotals,
                'family_shares' => $allFamiliesTotal > 0
                    ? $this->toShares($familyTotals, $allFamiliesTotal)
                    : ['matching' => 0.0, 'integration' => 0.0, 'ai' => 0.0, 'mobile' => 0.0],
                'total_events' => $allFamiliesTotal,
                'top_events' => $this->topEvents($from, 8),
            ],
        ];
    }

    private function count(string $eventName, Carbon $from): int
    {
        return ProductMetricEvent::query()
            ->where('event_name', $eventName)
            ->where('occurred_at', '>=', $from)
            ->count();
    }

    private function countByPrefix(string $prefix, Carbon $from): int
    {
        return ProductMetricEvent::query()
            ->where('event_name', 'like', $prefix.'%')
            ->where('occurred_at', '>=', $from)
            ->count();
    }

    /**
     * @return array<int,array{channel:string,total:int}>
     */
    private function channelsBreakdown(Carbon $from): array
    {
        /** @var Builder $query */
        $query = ProductMetricEvent::query()
            ->selectRaw('channel, COUNT(*) as total')
            ->where('event_name', 'mobile.sync_manifest.requested')
            ->where('occurred_at', '>=', $from)
            ->groupBy('channel')
            ->orderByDesc('total');

        return $query->get()
            ->map(static fn (ProductMetricEvent $item): array => [
                'channel' => (string) $item->channel,
                'total' => (int) $item->getAttribute('total'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $eventNames
     * @return list<array{date:string,total:int}>
     */
    private function dailySeries(array $eventNames, Carbon $from, int $days): array
    {
        $raw = ProductMetricEvent::query()
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->whereIn('event_name', $eventNames)
            ->where('occurred_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(static fn (ProductMetricEvent $row): array => [
                (string) $row->getAttribute('day') => (int) $row->getAttribute('total'),
            ])
            ->all();

        return $this->fillDailySeries($raw, $from, $days);
    }

    /**
     * @param  list<string>  $prefixes
     * @return list<array{date:string,total:int}>
     */
    private function dailySeriesByPrefix(array $prefixes, Carbon $from, int $days): array
    {
        $query = ProductMetricEvent::query()
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->where('occurred_at', '>=', $from);

        $query->where(static function (Builder $builder) use ($prefixes): void {
            foreach ($prefixes as $idx => $prefix) {
                if ($idx === 0) {
                    $builder->where('event_name', 'like', $prefix.'%');
                } else {
                    $builder->orWhere('event_name', 'like', $prefix.'%');
                }
            }
        });

        $raw = $query
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(static fn (ProductMetricEvent $row): array => [
                (string) $row->getAttribute('day') => (int) $row->getAttribute('total'),
            ])
            ->all();

        return $this->fillDailySeries($raw, $from, $days);
    }

    /**
     * @param  array<string,int>  $raw
     * @return list<array{date:string,total:int}>
     */
    private function fillDailySeries(array $raw, Carbon $from, int $days): array
    {
        $points = [];
        $cursor = $from->copy()->startOfDay();

        for ($i = 0; $i <= $days; $i++) {
            $date = $cursor->toDateString();
            $points[] = [
                'date' => $date,
                'total' => (int) ($raw[$date] ?? 0),
            ];
            $cursor->addDay();
        }

        return $points;
    }

    /**
     * @param  array<string,int>  $familyTotals
     * @return array<string,float>
     */
    private function toShares(array $familyTotals, int $allFamiliesTotal): array
    {
        $shares = [];
        foreach ($familyTotals as $name => $total) {
            $shares[$name] = round(($total / $allFamiliesTotal) * 100, 1);
        }

        return $shares;
    }

    /**
     * @return list<array{name:string,total:int}>
     */
    private function topEvents(Carbon $from, int $limit = 8): array
    {
        /** @var Builder $query */
        $query = ProductMetricEvent::query()
            ->selectRaw('event_name, COUNT(*) as total')
            ->where('occurred_at', '>=', $from)
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(max(1, min(50, $limit)));

        return $query->get()
            ->map(static fn (ProductMetricEvent $item): array => [
                'name' => (string) $item->event_name,
                'total' => (int) $item->getAttribute('total'),
            ])
            ->values()
            ->all();
    }
}
