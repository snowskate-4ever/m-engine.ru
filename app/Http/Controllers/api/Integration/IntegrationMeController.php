<?php

declare(strict_types=1);

namespace App\Http\Controllers\api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IntegrationMeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var IntegrationApiToken|null $token */
        $token = $request->attributes->get('integration_api_token');
        $user = $request->attributes->get('integration_user');
        abort_unless($token && $user, 401);

        return response()->json([
            'api_version' => 'v1',
            'user_id' => $user->id,
            'token_id' => $token->id,
            'abilities' => $token->abilities ?? [],
        ]);
    }
}
