<?php

declare(strict_types=1);

namespace App\Enums;

enum KanbanAccessLevel: string
{
    case Viewer = 'viewer';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Viewer => 'Наблюдатель',
            self::Editor => 'Редактор',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::Editor;
    }

    public static function max(?self $a, ?self $b): ?self
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $a === self::Editor || $b === self::Editor ? self::Editor : self::Viewer;
    }
}
