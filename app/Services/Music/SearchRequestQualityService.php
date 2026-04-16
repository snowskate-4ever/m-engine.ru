<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\SearchRequest;

final class SearchRequestQualityService
{
    /**
     * @return array{score:float,reasons:list<string>,anti_spam_flags:list<string>}
     */
    public function evaluate(SearchRequest $request): array
    {
        $score = 1.0;
        $reasons = [];
        $flags = [];

        $description = trim((string) ($request->description ?? ''));
        if ($description === '') {
            $score -= 0.25;
            $reasons[] = 'missing_description';
        } elseif (mb_strlen($description) < 40) {
            $score -= 0.15;
            $reasons[] = 'short_description';
        }

        if ($request->city_id === null && (bool) $request->my_city_only) {
            $score -= 0.2;
            $flags[] = 'city_only_without_city';
        }

        if (($request->target_kind ?? '') === '') {
            $score -= 0.2;
            $reasons[] = 'missing_target_kind';
        }

        $duplicateCount = SearchRequest::query()
            ->where('created_by_user_id', $request->created_by_user_id)
            ->where('id', '!=', $request->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('description', $request->description)
            ->count();
        if ($duplicateCount > 0) {
            $score -= 0.2;
            $flags[] = 'duplicate_description_24h';
        }

        return [
            'score' => max(0.0, min(1.0, round($score, 3))),
            'reasons' => $reasons,
            'anti_spam_flags' => $flags,
        ];
    }
}
