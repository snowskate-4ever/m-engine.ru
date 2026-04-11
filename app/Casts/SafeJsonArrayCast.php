<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Decodes JSON columns without throwing when content is not a JSON object/array.
 */
final class SafeJsonArrayCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value)) {
            return null;
        }
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
