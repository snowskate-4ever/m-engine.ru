<?php

declare(strict_types=1);

namespace App\Http;

use App\Services\Ai\AiServerQuotaDeniedException;
use Illuminate\Http\JsonResponse;

/**
 * Стабильные JSON-ошибки для мобильных/API (план §15): поля code + message.
 */
final class ApiErrorResponse
{
    public static function json(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
        ], $status);
    }

    public static function fromAiServerQuotaDenied(AiServerQuotaDeniedException $e): JsonResponse
    {
        $code = $e->errorCode === 'model_not_in_plan'
            ? 'subscription_required'
            : $e->errorCode;

        $status = $e->errorCode === 'model_not_in_plan' ? 403 : 402;

        return self::json($code, $e->getMessage(), $status);
    }
}
