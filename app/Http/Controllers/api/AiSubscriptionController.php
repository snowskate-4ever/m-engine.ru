<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiServerQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AiSubscriptionController extends Controller
{
    public function __construct(
        private readonly AiServerQuotaService $quota,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $user->refresh();
        $user->load('aiPreference');
        $sub = $this->quota->resolveActiveSubscription($user);
        $legacyUntil = $user->ai_subscription_valid_until;

        $tierPayload = null;
        $dailyCap = null;
        $allowedModelIds = null;

        if ($sub !== null && $sub->tier !== null) {
            $t = $sub->tier;
            $tierPayload = [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => $t->name,
                'price_monthly_rub' => $t->price_monthly_rub,
                'effective_price_monthly_rub' => $t->effectivePriceMonthlyRub(),
                'discount_percent' => $t->discount_percent,
                'discount_amount_fixed' => $t->discount_amount_fixed,
                'discount_valid_until' => $t->discount_valid_until?->toIso8601String(),
                'limits' => [
                    'tools_enabled' => $t->toolsEnabled(),
                    'max_ai_chats' => $t->maxAiChats(),
                    'server_tokens_per_month' => $t->serverTokensPerMonthCap(),
                ],
            ];
            $dailyCap = $t->serverRequestsPerDayCap();
            $allowedModelIds = $t->allowedServerModelIds();
        }

        $unlimited = false;
        if ($sub !== null && $sub->tier !== null && $sub->tier->serverRequestsPerDayCap() === null) {
            $unlimited = true;
        } elseif ($sub === null && $this->quota->hasLegacyPaidSubscription($user)) {
            $unlimited = true;
        }

        $usageDate = $this->quota->usageDateMoscow();
        $usedToday = $this->quota->getServerRequestsUsedToday($user);
        $effectiveLimit = $this->quota->effectiveServerDailyRequestLimit($user);
        $serverCeiling = $this->quota->serverSideDailyCeiling($user);
        $tokensCap = $this->quota->activeTierServerTokensPerMonthCap($user);
        $tokensUsed = $this->quota->serverTokensUsedThisQuotaMonth($user);

        $modelQ = $request->query('ai_server_model_id');
        $modelId = is_numeric($modelQ) ? (int) $modelQ : null;

        return response()->json([
            'legacy_subscription_valid_until' => $legacyUntil?->toIso8601String(),
            'subscription' => $sub === null ? null : [
                'id' => $sub->id,
                'status' => $sub->status->value,
                'current_period_start' => $sub->current_period_start?->toIso8601String(),
                'current_period_end' => $sub->current_period_end?->toIso8601String(),
                'payment_provider' => $sub->payment_provider,
                'tier' => $tierPayload,
            ],
            'preferences' => [
                'max_requests_per_day_self' => $user->aiPreference?->max_requests_per_day_self,
            ],
            'server_ai' => [
                'unlimited_daily_requests' => $unlimited,
                'daily_request_cap' => $dailyCap,
                'allowed_server_model_ids' => $allowedModelIds,
                'quota_timezone' => (string) config('billing.quota_timezone', 'Europe/Moscow'),
                'usage_date' => $usageDate,
                'server_requests_used_today' => $usedToday,
                'server_requests_daily_effective_limit' => $effectiveLimit,
                'server_requests_remaining_today' => $effectiveLimit === null
                    ? null
                    : max(0, $effectiveLimit - $usedToday),
                'server_side_daily_ceiling' => $serverCeiling,
                'server_tokens_monthly_cap' => $tokensCap,
                'server_tokens_used_this_month' => $tokensUsed,
                'server_tokens_remaining_this_month' => $tokensCap === null
                    ? null
                    : max(0, $tokensCap - $tokensUsed),
            ],
            'client_hints' => [
                'needs_subscription' => $this->quota->needsSubscriptionForServerAi($user),
                'server_ai_available' => $modelId !== null
                    ? $this->quota->serverAiAvailableForModel($user, $modelId)
                    : $this->quota->serverAiBaselineAvailable($user),
            ],
        ]);
    }
}
