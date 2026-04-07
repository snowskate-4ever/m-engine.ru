<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\MessageKind;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Avoids ValueError when DB contains an unknown kind value (list/detail would 500).
 */
final class SafeMessageKindCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): MessageKind
    {
        if ($value === null || $value === '') {
            return MessageKind::Text;
        }
        if ($value instanceof MessageKind) {
            return $value;
        }
        try {
            return MessageKind::from((string) $value);
        } catch (\ValueError) {
            return MessageKind::Text;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof MessageKind) {
            return $value->value;
        }

        return (string) $value;
    }
}
