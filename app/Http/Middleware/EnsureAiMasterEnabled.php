<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAiMasterEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('ai.enabled')) {
            return response()->json([
                'code' => 'ai_disabled',
                'message' => 'AI features are disabled.',
            ], 503);
        }

        return $next($request);
    }
}
