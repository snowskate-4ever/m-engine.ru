<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIntegrationAbility
{
    public function handle(Request $request, Closure $next, string $requiredAbility): Response
    {
        $abilities = $request->attributes->get('integration_abilities', []);
        if (! is_array($abilities)) {
            $abilities = [];
        }

        if ($abilities === [] || in_array('*', $abilities, true) || in_array($requiredAbility, $abilities, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Integration token does not have required ability.',
            'required_ability' => $requiredAbility,
        ], 403);
    }
}
