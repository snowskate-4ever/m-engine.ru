<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Analytics\ProductMetricsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogSlowApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $threshold = (int) config('observability.slow_api_request_ms', 0);
        if ($threshold < 1) {
            return $next($request);
        }

        $started = microtime(true);
        $response = $next($request);
        $ms = (int) round((microtime(true) - $started) * 1000);

        if ($ms >= $threshold) {
            Log::info('http.api.slow', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ms' => $ms,
                'status' => $response->getStatusCode(),
            ]);

            app(ProductMetricsService::class)->track('observability.http.slow_api', null, 'api', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ms' => $ms,
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
