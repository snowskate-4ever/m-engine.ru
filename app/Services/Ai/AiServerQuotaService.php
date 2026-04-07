<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiRequestSource;
use App\Enums\ConversationType;
use App\Enums\UserAiSubscriptionStatus;
use App\Models\AiUsageLedger;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserAiServerDayUsage;
use App\Models\UserAiSubscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AiServerQuotaService
{
    public function hasLegacyPaidSubscription(User $user): bool
    {
        $until = $user->ai_subscription_valid_until;

        return $until instanceof CarbonInterface && $until->isFuture();
    }

    /**
     * Активная подписка по БД (период не истёк).
     */
    public function resolveActiveSubscription(User $user): ?UserAiSubscription
    {
        return UserAiSubscription::query()
            ->where('user_id', $user->id)
            ->where('status', UserAiSubscriptionStatus::Active)
            ->where('current_period_end', '>', now())
            ->with('tier')
            ->orderByDesc('current_period_end')
            ->first();
    }

    /**
     * Дневной потолок **только от тарифа/наследия/триала** (без self-cap).
     * null — без лимита со стороны платформы (без учёта добровольного потолка).
     */
    public function serverSideDailyCeiling(User $user): ?int
    {
        $user = $user->fresh() ?? $user;
        $subscription = $this->resolveActiveSubscription($user);
        if ($subscription !== null && $subscription->tier !== null) {
            return $subscription->tier->serverRequestsPerDayCap();
        }

        if ($this->hasLegacyPaidSubscription($user)) {
            return null;
        }

        return $this->effectiveDailyLimit($user);
    }

    /**
     * Эффективный дневной лимит серверных запросов: min(потолок платформы, self-cap).
     * null — без лимита (не ведём счётчик за день для блокировки).
     */
    public function effectiveServerDailyRequestLimit(User $user): ?int
    {
        $user = $user->fresh(['aiPreference']) ?? $user;
        $server = $this->serverSideDailyCeiling($user);
        $self = $user->aiPreference?->max_requests_per_day_self;
        if ($self !== null && $self < 1) {
            $self = null;
        }

        if ($self === null) {
            return $server;
        }

        $maxSelfOnly = max(1, (int) config('billing.max_self_cap_requests_per_day', 100_000));
        if ($server === null) {
            return min($self, $maxSelfOnly);
        }

        return min($server, $self);
    }

    public function getServerRequestsUsedToday(User $user): int
    {
        $user = $user->fresh() ?? $user;

        return $this->countForDay($user, $this->usageDateMoscow());
    }

    /**
     * Какие id `ai_server_models` показывать в каталоге API.
     *
     * @return null|list<int> null — все активные; [] — ни одной; иначе фильтр по id
     */
    public function allowedServerModelIdsForApi(User $user): ?array
    {
        $user = $user->fresh() ?? $user;
        $subscription = $this->resolveActiveSubscription($user);
        if ($subscription === null || $subscription->tier === null) {
            return null;
        }

        return $subscription->tier->allowedServerModelIds();
    }

    /**
     * @throws AiServerQuotaDeniedException
     */
    public function assertServerModelAllowedForPlan(User $user, ?int $aiServerModelId): void
    {
        if ($aiServerModelId === null) {
            return;
        }

        $user = $user->fresh() ?? $user;
        $subscription = $this->resolveActiveSubscription($user);
        if ($subscription === null || $subscription->tier === null) {
            return;
        }

        $allowed = $subscription->tier->allowedServerModelIds();
        if ($allowed === null) {
            return;
        }

        if ($allowed === []) {
            throw new AiServerQuotaDeniedException(
                'model_not_in_plan',
                'This server model is not included in your subscription plan.',
            );
        }

        if (! in_array($aiServerModelId, $allowed, true)) {
            throw new AiServerQuotaDeniedException(
                'model_not_in_plan',
                'This server model is not included in your subscription plan.',
            );
        }
    }

    /**
     * @throws AiServerQuotaDeniedException
     */
    public function assertMayConsumeServerAiRequest(User $user): void
    {
        $user = $user->fresh() ?? $user;

        $limit = $this->effectiveServerDailyRequestLimit($user);
        if ($limit === null) {
            return;
        }

        $date = $this->usageDateMoscow();
        $count = $this->countForDay($user, $date);
        if ($count >= $limit) {
            throw new AiServerQuotaDeniedException(
                'quota_exceeded',
                'Server AI daily limit reached. Try again after midnight (MSK) or use your own API key (BYOK).',
            );
        }
    }

    public function recordSuccessfulServerAiRequest(User $user): void
    {
        $user = $user->fresh() ?? $user;

        $limit = $this->effectiveServerDailyRequestLimit($user);
        if ($limit === null) {
            return;
        }

        $subscription = $this->resolveActiveSubscription($user);
        $isTrialOrUnpaidPath = $subscription === null && ! $this->hasLegacyPaidSubscription($user);

        if ($isTrialOrUnpaidPath) {
            DB::transaction(function () use ($user): void {
                $locked = User::query()->lockForUpdate()->find($user->id);
                if ($locked === null) {
                    return;
                }
                if ($this->effectiveServerDailyRequestLimit($locked) === null) {
                    return;
                }
                if ($this->resolveActiveSubscription($locked) !== null || $this->hasLegacyPaidSubscription($locked)) {
                    $this->incrementDayUsageWithoutTrialLocked($locked);

                    return;
                }
                if ($locked->ai_trial_started_at === null) {
                    $locked->forceFill(['ai_trial_started_at' => now()])->save();
                }

                $date = $this->usageDateMoscow();
                $usage = UserAiServerDayUsage::query()->firstOrNew([
                    'user_id' => $locked->id,
                    'usage_date' => $date,
                ]);
                $usage->request_count = (int) $usage->request_count + 1;
                $usage->save();
            });

            return;
        }

        $this->incrementDayUsageWithoutTrial($user);
    }

    public function usageDateMoscow(): string
    {
        $tz = (string) config('billing.quota_timezone', 'Europe/Moscow');

        return now()->timezone($tz)->toDateString();
    }

    public function serverTierToolsEnabled(User $user): bool
    {
        $sub = $this->resolveActiveSubscription($user);
        if ($sub === null || $sub->tier === null) {
            return true;
        }

        return $sub->tier->toolsEnabled();
    }

    public function activeTierServerTokensPerMonthCap(User $user): ?int
    {
        $sub = $this->resolveActiveSubscription($user);
        if ($sub === null || $sub->tier === null) {
            return null;
        }

        return $sub->tier->serverTokensPerMonthCap();
    }

    public function serverTokensUsedThisQuotaMonth(User $user): int
    {
        [$start, $end] = $this->currentQuotaMonthBoundsUtc();

        $v = AiUsageLedger::query()
            ->where('user_id', $user->id)
            ->where('source', AiRequestSource::Server->value)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(tokens_prompt + tokens_completion), 0) as agg')
            ->value('agg');

        return (int) $v;
    }

    /**
     * Нужна ли пользователю платная подписка, чтобы пользоваться серверным ИИ (нет триала/бесплатной квоты).
     */
    public function needsSubscriptionForServerAi(User $user): bool
    {
        $user = $user->fresh() ?? $user;
        if ($this->resolveActiveSubscription($user) !== null) {
            return false;
        }
        if ($this->hasLegacyPaidSubscription($user)) {
            return false;
        }

        return $this->serverSideDailyCeiling($user) === 0;
    }

    /**
     * Можно ли сейчас отправить хотя бы одно серверное сообщение (дневной и месячный токенный лимит), без проверки конкретной модели.
     */
    public function serverAiBaselineAvailable(User $user): bool
    {
        try {
            $this->assertMayConsumeServerTokensThisMonth($user);
            $this->assertMayConsumeServerAiRequest($user);

            return true;
        } catch (AiServerQuotaDeniedException) {
            return false;
        }
    }

    /**
     * Учитывая модель чата, прошли бы проверки квот/плана перед отправкой в серверный ИИ.
     */
    public function serverAiAvailableForModel(User $user, ?int $aiServerModelId): bool
    {
        if ($aiServerModelId === null) {
            return false;
        }

        try {
            $this->assertMayConsumeServerTokensThisMonth($user);
            $this->assertMayConsumeServerAiRequest($user);
            $this->assertServerModelAllowedForPlan($user, $aiServerModelId);

            return true;
        } catch (AiServerQuotaDeniedException) {
            return false;
        }
    }

    /**
     * @throws AiServerQuotaDeniedException
     */
    public function assertMayConsumeServerTokensThisMonth(User $user): void
    {
        $cap = $this->activeTierServerTokensPerMonthCap($user);
        if ($cap === null) {
            return;
        }

        $used = $this->serverTokensUsedThisQuotaMonth($user);
        if ($used >= $cap) {
            throw new AiServerQuotaDeniedException(
                'token_quota_exceeded',
                'Monthly server token limit reached for your plan. Try next month or use your own API key (BYOK).',
            );
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertMayCreateAnotherAiChat(User $user): void
    {
        $user = $user->fresh() ?? $user;
        $sub = $this->resolveActiveSubscription($user);
        $max = $sub?->tier?->maxAiChats();
        if ($max === null) {
            return;
        }

        $count = Conversation::query()
            ->where('type', ConversationType::Ai)
            ->whereHas('participants', static fn ($q) => $q->where('users.id', $user->id))
            ->count();

        if ($count >= $max) {
            throw ValidationException::withMessages([
                'type' => [__('ui.messenger.ai_max_ai_chats', ['max' => $max])],
            ]);
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function currentQuotaMonthBoundsUtc(): array
    {
        $tz = (string) config('billing.quota_timezone', 'Europe/Moscow');
        $now = now()->timezone($tz);
        $start = $now->copy()->startOfMonth()->utc();
        $end = $now->copy()->endOfMonth()->utc();

        return [$start, $end];
    }

    private function incrementDayUsageWithoutTrial(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::query()->lockForUpdate()->find($user->id);
            if ($locked === null) {
                return;
            }
            $this->incrementDayUsageWithoutTrialLocked($locked);
        });
    }

    private function incrementDayUsageWithoutTrialLocked(User $locked): void
    {
        $date = $this->usageDateMoscow();
        $usage = UserAiServerDayUsage::query()->firstOrNew([
            'user_id' => $locked->id,
            'usage_date' => $date,
        ]);
        $usage->request_count = (int) $usage->request_count + 1;
        $usage->save();
    }

    private function effectiveDailyLimit(User $user): int
    {
        if ($user->ai_trial_started_at === null) {
            return max(1, (int) config('billing.trial_max_requests_per_day'));
        }

        if ($this->inTrialWindow($user)) {
            return max(1, (int) config('billing.trial_max_requests_per_day'));
        }

        return max(0, (int) config('billing.unpaid_daily_server_request_allowance'));
    }

    private function inTrialWindow(User $user): bool
    {
        $started = $user->ai_trial_started_at;
        if ($started === null) {
            return false;
        }

        $days = (int) config('billing.trial_duration_days', 14);
        $ends = $started->copy()->addDays(max(1, $days));

        return now()->lt($ends);
    }

    private function countForDay(User $user, string $date): int
    {
        $row = UserAiServerDayUsage::query()
            ->where('user_id', $user->id)
            ->whereDate('usage_date', $date)
            ->first();

        return $row !== null ? (int) $row->request_count : 0;
    }
}
