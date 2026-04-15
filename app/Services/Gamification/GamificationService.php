<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserXpLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class GamificationService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function addXp(User $user, int $delta, string $reason, ?Model $context = null, array $meta = []): UserXpLedger
    {
        return DB::transaction(function () use ($user, $delta, $reason, $context, $meta): UserXpLedger {
            return UserXpLedger::query()->create([
                'user_id' => $user->id,
                'delta' => $delta,
                'reason' => $reason,
                'context_type' => $context?->getMorphClass(),
                'context_id' => $context?->getKey(),
                'meta' => $meta,
                'created_at' => now(),
            ]);
        });
    }

    public function totalXp(User $user): int
    {
        return (int) UserXpLedger::query()->where('user_id', $user->id)->sum('delta');
    }

    /**
     * @return list<array{user_id:int,total_xp:int}>
     */
    public function leaderboard(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        $rows = UserXpLedger::query()
            ->selectRaw('user_id, SUM(delta) as total_xp')
            ->groupBy('user_id')
            ->orderByDesc('total_xp')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'user_id' => (int) $row->user_id,
                'total_xp' => (int) $row->total_xp,
            ];
        }

        return $out;
    }

    public function unlockAchievement(User $user, Achievement $achievement, array $meta = []): ?UserAchievement
    {
        if (! $achievement->is_active) {
            return null;
        }

        $pivot = UserAchievement::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
            ],
            [
                'unlocked_at' => now(),
                'meta' => $meta,
            ],
        );

        if ($pivot->wasRecentlyCreated && $achievement->xp_reward > 0) {
            $this->addXp($user, $achievement->xp_reward, 'achievement:'.$achievement->slug, $achievement);
        }

        return $pivot;
    }
}
