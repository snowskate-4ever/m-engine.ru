<?php

declare(strict_types=1);

namespace App\Enums;

enum KanbanVisibilityMode: string
{
    case Inherit = 'inherit';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Inherit => 'Как у доски',
            self::Custom => 'Свой список доступа',
        };
    }
}
