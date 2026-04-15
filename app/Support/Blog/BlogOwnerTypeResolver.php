<?php

declare(strict_types=1);

namespace App\Support\Blog;

use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Studio;
use App\Models\Teacher;
use InvalidArgumentException;

final class BlogOwnerTypeResolver
{
    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static function classFromAlias(string $alias): string
    {
        $key = strtolower(trim($alias));

        return match ($key) {
            'musician' => Musician::class,
            'teacher' => Teacher::class,
            'performer', 'peformer' => Peformer::class,
            'studio' => Studio::class,
            default => throw new InvalidArgumentException('Unsupported blog owner type: '.$alias),
        };
    }
}
