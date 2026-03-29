<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiRequestSource;
use App\Models\AiServerModel;
use App\Models\AiUsageLedger;
use App\Models\User;

final class AiUsageLedgerWriter
{
    public function recordServerSuccess(
        User $user,
        ?int $aiServerModelId,
        int $tokensPrompt,
        int $tokensCompletion,
        ?int $conversationId,
        ?string $estimatedInternalCost = null,
    ): void {
        $cost = $estimatedInternalCost ?? $this->estimateInternalCost($aiServerModelId, $tokensPrompt, $tokensCompletion);

        AiUsageLedger::query()->create([
            'user_id' => $user->id,
            'ai_server_model_id' => $aiServerModelId,
            'source' => AiRequestSource::Server->value,
            'tokens_prompt' => $tokensPrompt,
            'tokens_completion' => $tokensCompletion,
            'estimated_internal_cost' => $cost,
            'conversation_id' => $conversationId,
            'created_at' => now(),
        ]);
    }

    public function recordByokSuccess(
        User $user,
        int $tokensPrompt,
        int $tokensCompletion,
        ?int $conversationId,
    ): void {
        AiUsageLedger::query()->create([
            'user_id' => $user->id,
            'ai_server_model_id' => null,
            'source' => AiRequestSource::Byok->value,
            'tokens_prompt' => $tokensPrompt,
            'tokens_completion' => $tokensCompletion,
            'estimated_internal_cost' => null,
            'conversation_id' => $conversationId,
            'created_at' => now(),
        ]);
    }

    /**
     * Та же оценка, что пишется в ledger (§2.13).
     */
    public function estimateServerInternalCost(?int $modelId, int $promptTokens, int $completionTokens): ?string
    {
        return $this->estimateInternalCost($modelId, $promptTokens, $completionTokens);
    }

    private function estimateInternalCost(?int $modelId, int $promptTokens, int $completionTokens): ?string
    {
        if ($modelId === null) {
            return null;
        }

        $model = AiServerModel::query()->find($modelId);
        if ($model === null) {
            return null;
        }

        $perPrompt = $model->internal_cost_per_1k_prompt_tokens;
        $perCompletion = $model->internal_cost_per_1k_completion_tokens;
        if ($perPrompt === null && $perCompletion === null) {
            return null;
        }

        $sum = 0.0;
        if ($perPrompt !== null) {
            $sum += (float) $perPrompt * $promptTokens / 1000.0;
        }
        if ($perCompletion !== null) {
            $sum += (float) $perCompletion * $completionTokens / 1000.0;
        }

        return number_format($sum, 6, '.', '');
    }
}
