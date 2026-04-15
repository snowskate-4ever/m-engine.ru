<?php

declare(strict_types=1);

namespace App\Services\Ai\Expansion;

use App\Models\User;

final class PartnerRecommender
{
    /**
     * @return list<array{user_id:int,score:float,reason:string}>
     */
    public function recommendForUser(User $user, int $limit = 10): array
    {
        if (! config('ai_expansion.recommender_enabled')) {
            return [];
        }

        return [
            ['user_id' => $user->id, 'score' => 0.01, 'reason' => 'stub_self_reference'],
        ];
    }
}
