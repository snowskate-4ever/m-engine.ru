<?php

declare(strict_types=1);

namespace App\Support\Moderation;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

final class AuditActor
{
    /**
     * @return array{0: ?string, 1: int|string|null}
     */
    public static function resolve(): array
    {
        $guards = ['web', 'moonshine'];
        foreach ($guards as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user instanceof Authenticatable) {
                return [$user::class, $user->getAuthIdentifier()];
            }
        }

        return [null, null];
    }
}
