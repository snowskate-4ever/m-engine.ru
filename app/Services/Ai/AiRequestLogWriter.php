<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiRequestSource;
use App\Enums\AiRequestStatus;
use App\Models\AiRequestLog;
use App\Models\User;

final class AiRequestLogWriter
{
    public function write(
        User $user,
        AiRequestSource $source,
        AiRequestStatus $status,
        int $durationMs,
        int $tokensPrompt = 0,
        int $tokensCompletion = 0,
        ?int $conversationId = null,
        ?int $aiServerModelId = null,
        ?int $userAiConnectionId = null,
        ?int $httpStatus = null,
        ?string $providerErrorCode = null,
        ?string $errorMessage = null,
        ?string $providerRequestId = null,
        ?string $estimatedInternalCost = null,
        ?string $promptText = null,
        ?string $responseText = null,
    ): AiRequestLog {
        $pMax = (int) config('ai.request_log.prompt_excerpt_max', 1000);
        $rMax = (int) config('ai.request_log.response_excerpt_max', 1000);
        $storeFullPrompt = (bool) config('ai.request_log.store_full_prompt', false);
        $storeFullResponse = (bool) config('ai.request_log.store_full_response', false);

        return AiRequestLog::query()->create([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'source' => $source,
            'ai_server_model_id' => $aiServerModelId,
            'user_ai_connection_id' => $userAiConnectionId,
            'duration_ms' => $durationMs,
            'status' => $status,
            'http_status' => $httpStatus,
            'provider_error_code' => $providerErrorCode,
            'error_message' => $errorMessage,
            'tokens_prompt' => $tokensPrompt,
            'tokens_completion' => $tokensCompletion,
            'estimated_internal_cost' => $estimatedInternalCost,
            'provider_request_id' => $providerRequestId,
            'prompt_excerpt' => $this->excerpt($promptText, $pMax),
            'response_excerpt' => $this->excerpt($responseText, $rMax),
            'prompt_full' => $storeFullPrompt ? $promptText : null,
            'response_full' => $storeFullResponse ? $responseText : null,
        ]);
    }

    private function excerpt(?string $text, int $maxChars): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }
        if ($maxChars <= 0) {
            return '';
        }

        return mb_strlen($text) <= $maxChars
            ? $text
            : mb_substr($text, 0, $maxChars);
    }
}
