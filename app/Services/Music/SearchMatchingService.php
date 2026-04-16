<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\AiProvider;
use App\Models\ConcertVenue;
use App\Models\MatchingRunLog;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\SearchRequest;
use App\Models\SearchRequestMatch;
use App\Models\SearchRequestResponse;
use App\Models\Studio;
use App\Models\User;
use App\Services\Ai\OpenAiChatCompletionClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SearchMatchingService
{
    public function __construct(
        private readonly OpenAiChatCompletionClient $aiClient,
    ) {}

    public function run(
        string $scope = 'all',
        ?User $runBy = null,
        bool $isAutomatic = true,
        bool $dryRun = false,
        ?int $maxRequests = null,
        string $explanationLevel = 'off',
        array $runParams = [],
    ): MatchingRunLog {
        $startedAt = now();
        $log = MatchingRunLog::query()->create([
            'run_by_user_id' => $runBy?->id,
            'is_automatic' => $isAutomatic,
            'scope' => $scope,
            'started_at' => $startedAt,
        ]);

        $processed = 0;
        $matched = 0;
        $failed = 0;
        $trace = [];

        $query = SearchRequest::query()
            ->where('ad_status', 'active')
            ->where('moderation_status', 'approved')
            ->whereNotNull('target_kind')
            ->when($scope === 'profiles', function ($q): void {
                $q->whereIn('target_kind', [
                    'musician',
                    'session',
                    'teacher',
                    'organizer',
                    'agent',
                    'sound_engineer',
                    'arranger',
                    'live_sound',
                    'lighting_designer',
                    'videographer',
                    'photographer',
                ]);
            })
            ->when($scope === 'entities', function ($q): void {
                $q->whereIn('target_kind', [
                    'performer',
                    'studio',
                    'rehearsal',
                    'school',
                    'label',
                    'production',
                    'venue',
                ]);
            })
            ->orderBy('id');

        if ($maxRequests !== null) {
            $query->limit(max(1, $maxRequests));
        }

        foreach ($query->get() as $request) {
            try {
                $processed++;
                $result = $this->matchOne($request, $dryRun, $explanationLevel);
                $matched += $result['matched'];
                if ($explanationLevel !== 'off') {
                    $trace[] = $result['trace'];
                }
            } catch (\Throwable) {
                $failed++;
            }
        }

        $log->forceFill([
            'processed_count' => $processed,
            'matched_count' => $matched,
            'failed_count' => $failed,
            'finished_at' => now(),
            'meta' => [
                'ai_enabled' => $this->aiEnabled(),
                'threshold' => (float) config('ai.matching.score_threshold', 0.65),
                'dry_run' => $dryRun,
                'max_requests' => $maxRequests,
                'explanation_level' => $explanationLevel,
                'trace' => $this->normalizeTraceByLevel($trace, $explanationLevel),
                'run_params' => $runParams,
            ],
        ])->save();

        return $log;
    }

    /**
     * @return array{matched:int,trace:array<string,mixed>}
     */
    private function matchOne(SearchRequest $request, bool $dryRun, string $explanationLevel): array
    {
        $candidateClass = $this->resolveTargetModel($request->target_kind);
        if ($candidateClass === null) {
            return [
                'matched' => 0,
                'trace' => [
                    'search_request_id' => $request->id,
                    'target_kind' => (string) $request->target_kind,
                    'skipped' => 'unsupported_target_kind',
                ],
            ];
        }

        /** @var list<Model> $candidates */
        $candidates = $candidateClass::query()->limit(30)->get()->all();
        if ($candidates === []) {
            return [
                'matched' => 0,
                'trace' => [
                    'search_request_id' => $request->id,
                    'target_kind' => (string) $request->target_kind,
                    'candidate_count' => 0,
                ],
            ];
        }

        $threshold = (float) config('ai.matching.score_threshold', 0.65);
        $matched = 0;

        $trace = [
            'search_request_id' => $request->id,
            'target_kind' => (string) $request->target_kind,
            'candidate_count' => count($candidates),
            'matched_count' => 0,
            'candidates' => [],
        ];

        DB::transaction(function () use ($request, $candidates, $threshold, &$matched, $dryRun, $explanationLevel, &$trace): void {
            if ($dryRun) {
                foreach ($candidates as $candidate) {
                    $ruleScore = $this->ruleScore($request, $candidate);
                    $behaviorScore = $this->behaviorScore($request);
                    $hybridScore = $this->hybridizeScore($ruleScore, $behaviorScore);
                    [$score, $explanation, $mode] = $this->scoreWithAiIfAvailable($request, $candidate, $hybridScore);
                    $isMatch = $score >= $threshold;
                    if ($isMatch) {
                        $matched++;
                    }
                    $this->appendCandidateTrace($trace, $explanationLevel, $candidate, $ruleScore, $score, $threshold, $isMatch, $mode, $explanation);
                }

                return;
            }

            SearchRequestMatch::query()
                ->where('search_request_id', $request->id)
                ->delete();

            foreach ($candidates as $candidate) {
                $ruleScore = $this->ruleScore($request, $candidate);
                $behaviorScore = $this->behaviorScore($request);
                $hybridScore = $this->hybridizeScore($ruleScore, $behaviorScore);
                [$score, $explanation, $mode] = $this->scoreWithAiIfAvailable($request, $candidate, $hybridScore);
                $isMatch = $score >= $threshold;
                $this->appendCandidateTrace($trace, $explanationLevel, $candidate, $ruleScore, $score, $threshold, $isMatch, $mode, $explanation);
                if (! $isMatch) {
                    continue;
                }

                SearchRequestMatch::query()->create([
                    'search_request_id' => $request->id,
                    'candidate_type' => $candidate::class,
                    'candidate_id' => (int) $candidate->getKey(),
                    'score' => $score,
                    'meta' => [
                        'mode' => $mode,
                        'rule_score' => $ruleScore,
                        'behavior_score' => $behaviorScore,
                        'hybrid_score' => $hybridScore,
                        'explanation' => $explanation,
                    ],
                ]);
                $matched++;
            }
        });

        $trace['matched_count'] = $matched;

        return ['matched' => $matched, 'trace' => $trace];
    }

    private function behaviorScore(SearchRequest $request): float
    {
        $base = 0.5;

        $recentOwnRequests = SearchRequest::query()
            ->where('created_by_user_id', $request->created_by_user_id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        if ($recentOwnRequests > 0) {
            $base += min(0.15, $recentOwnRequests * 0.01);
        }

        $responsesForOwnRequests = SearchRequestResponse::query()
            ->whereHas('searchRequest', function ($q) use ($request): void {
                $q->where('created_by_user_id', $request->created_by_user_id)
                    ->where('created_at', '>=', now()->subDays(30));
            })
            ->count();
        if ($responsesForOwnRequests > 0) {
            $base += min(0.2, $responsesForOwnRequests * 0.01);
        }

        $canceledRequests = SearchRequest::query()
            ->where('created_by_user_id', $request->created_by_user_id)
            ->where('status', 'canceled')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        if ($canceledRequests > 0) {
            $base -= min(0.2, $canceledRequests * 0.03);
        }

        return max(0.0, min(1.0, $base));
    }

    private function hybridizeScore(float $ruleScore, float $behaviorScore): float
    {
        return max(0.0, min(1.0, ($ruleScore * 0.7) + ($behaviorScore * 0.3)));
    }

    /**
     * @param  array<string,mixed>  $trace
     */
    private function appendCandidateTrace(
        array &$trace,
        string $explanationLevel,
        Model $candidate,
        float $ruleScore,
        float $score,
        float $threshold,
        bool $isMatch,
        string $mode,
        string $explanation,
    ): void {
        if ($explanationLevel === 'off') {
            return;
        }

        if ($explanationLevel === 'summary') {
            $trace['top_candidate'] ??= [
                'candidate_type' => $candidate::class,
                'candidate_id' => (int) $candidate->getKey(),
                'score' => $score,
                'is_match' => $isMatch,
                'mode' => $mode,
            ];
            if ($score > (float) ($trace['top_candidate']['score'] ?? 0)) {
                $trace['top_candidate'] = [
                    'candidate_type' => $candidate::class,
                    'candidate_id' => (int) $candidate->getKey(),
                    'score' => $score,
                    'is_match' => $isMatch,
                    'mode' => $mode,
                ];
            }

            return;
        }

        $items = $trace['candidates'] ?? [];
        if (count($items) >= 20) {
            return;
        }

        $items[] = [
            'candidate_type' => $candidate::class,
            'candidate_id' => (int) $candidate->getKey(),
            'rule_score' => $ruleScore,
            'final_score' => $score,
            'threshold' => $threshold,
            'is_match' => $isMatch,
            'mode' => $mode,
            'explanation' => $explanation,
        ];
        $trace['candidates'] = $items;
    }

    /**
     * @param  list<array<string,mixed>>  $trace
     * @return list<array<string,mixed>>
     */
    private function normalizeTraceByLevel(array $trace, string $level): array
    {
        if ($level === 'off') {
            return [];
        }

        return array_slice($trace, 0, 30);
    }

    private function ruleScore(SearchRequest $request, Model $candidate): float
    {
        $score = 0.45;

        if ($request->city_id !== null && isset($candidate->city_id) && (int) $candidate->city_id === (int) $request->city_id) {
            $score += 0.35;
        } elseif ((bool) $request->my_city_only) {
            $score -= 0.2;
        }

        if (($request->description ?? '') !== '' && isset($candidate->name) && stripos((string) $request->description, (string) $candidate->name) !== false) {
            $score += 0.1;
        }

        return max(0.0, min(1.0, $score));
    }

    /**
     * @return array{0: float, 1: string, 2: string}
     */
    private function scoreWithAiIfAvailable(SearchRequest $request, Model $candidate, float $ruleScore): array
    {
        if (! $this->aiEnabled()) {
            return [$ruleScore, 'Rule-based fallback score.', 'rule'];
        }

        $provider = AiProvider::query()
            ->where('is_active', true)
            ->where('driver', (string) config('ai.matching.provider', 'openai'))
            ->orderBy('sort_order')
            ->first();

        $baseUrl = (string) ($provider?->config['base_url'] ?? config('ai.openai.base_url'));
        $apiKey = (string) ($provider?->config['api_key'] ?? config('ai.openai.server_api_key'));
        $model = (string) config('ai.matching.model', 'gpt-4o-mini');
        if ($apiKey === '') {
            return [$ruleScore, 'AI matching key missing, fallback to rule score.', 'rule'];
        }

        $payload = [
            'request' => [
                'description' => (string) ($request->description ?? ''),
                'target_kind' => (string) ($request->target_kind ?? ''),
                'city_id' => $request->city_id,
            ],
            'candidate' => [
                'id' => (int) $candidate->getKey(),
                'name' => (string) ($candidate->name ?? ''),
                'city_id' => $candidate->city_id ?? null,
            ],
            'rule_score' => $ruleScore,
        ];

        $response = $this->aiClient->chat(
            $baseUrl,
            $apiKey,
            $model,
            [
                ['role' => 'system', 'content' => 'Return strict JSON: {"score":0..1,"explanation":"..."}'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            (int) config('ai.openai.timeout_seconds', 60),
            null,
        );

        if (! ($response['ok'] ?? false)) {
            return [$ruleScore, 'AI request failed, fallback to rule score.', 'rule'];
        }

        $content = (string) ($response['content'] ?? '');
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return [$ruleScore, 'AI returned non-JSON response, fallback to rule score.', 'rule'];
        }

        $score = isset($decoded['score']) ? (float) $decoded['score'] : $ruleScore;
        $explanation = isset($decoded['explanation']) ? (string) $decoded['explanation'] : 'AI score without explanation.';

        return [max(0.0, min(1.0, $score)), $explanation, 'ai'];
    }

    private function aiEnabled(): bool
    {
        return (bool) config('ai.enabled', true) && (bool) config('ai.matching.enabled', true);
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveTargetModel(?string $targetKind): ?string
    {
        return match ((string) $targetKind) {
            'performer' => Peformer::class,
            'musician', 'session', 'teacher' => Musician::class,
            'venue', 'organizer' => ConcertVenue::class,
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'school' => School::class,
            'label' => RecordLabel::class,
            'production' => ProducerCenter::class,
            'agent' => User::class,
            default => null,
        };
    }
}
