<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Публичные флаги возможностей для клиентов (мобилки могут вызывать до логина).
 */
final class ApiFeaturesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'ai_enabled' => (bool) config('ai.enabled'),
                'ai_api_version' => '1',
            ],
        ]);
    }
}
