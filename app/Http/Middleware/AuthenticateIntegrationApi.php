<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IntegrationApiToken;
use App\Services\Analytics\ProductMetricsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateIntegrationApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Missing integration Bearer token.'], 401);
        }

        $plain = trim(substr($header, 7));
        if ($plain === '') {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $hash = IntegrationApiToken::hashPlainToken($plain);
        $token = IntegrationApiToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
        if ($token === null) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $request->attributes->set('integration_api_token', $token);
        $request->attributes->set('integration_user', $token->user);
        $request->attributes->set('integration_abilities', is_array($token->abilities) ? $token->abilities : []);

        Cache::remember('integration:last_used:'.$token->id, 120, function () use ($token): int {
            $token->forceFill(['last_used_at' => now()])->save();

            return 1;
        });

        app(ProductMetricsService::class)->track(
            str_starts_with((string) $request->path(), 'api/integration/v2/')
                ? 'integration.v2.request'
                : 'integration.v1.request',
            $token->user_id,
            'integration',
            ['path' => (string) $request->path(), 'method' => (string) $request->method()]
        );

        return $next($request);
    }
}
