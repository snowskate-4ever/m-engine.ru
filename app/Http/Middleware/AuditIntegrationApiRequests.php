<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IntegrationApiAuditLog;
use App\Models\IntegrationApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuditIntegrationApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $response = $next($request);

        $token = $request->attributes->get('integration_api_token');
        if ($token instanceof IntegrationApiToken) {
            IntegrationApiAuditLog::query()->create([
                'integration_api_token_id' => $token->id,
                'user_id' => $token->user_id,
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
