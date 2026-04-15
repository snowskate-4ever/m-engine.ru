<?php

declare(strict_types=1);

namespace App\Services\Ai\Expansion;

/**
 * Заготовка авто-модерации: эвристика + будущий вызов LLM под feature-flag.
 */
final class ContentModerationScorer
{
    /**
     * @return array{score: float, flags: list<string>}
     */
    public function scoreText(string $text): array
    {
        if (! config('ai_expansion.auto_moderation_enabled')) {
            return ['score' => 0.0, 'flags' => ['disabled']];
        }

        $flags = [];
        $t = mb_strtolower($text);
        if (str_contains($t, 'http://') || str_contains($t, 'https://')) {
            $flags[] = 'contains_url';
        }
        if (preg_match('/\b(spam|scam)\b/u', $t)) {
            $flags[] = 'keyword_hit';
        }

        $score = $flags === [] ? 0.05 : min(1.0, 0.2 + count($flags) * 0.15);

        return ['score' => $score, 'flags' => $flags];
    }
}
