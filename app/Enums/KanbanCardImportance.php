<?php

declare(strict_types=1);

namespace App\Enums;

enum KanbanCardImportance: string
{
    case Normal = 'normal';
    case Low = 'low';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Обычная',
            self::Low => 'Низкая',
            self::High => 'Высокая',
            self::Urgent => 'Срочная',
        };
    }

    /**
     * Классы для карточки на доске: рамка + полупрозрачная заливка.
     */
    public function boardCardClasses(): string
    {
        return match ($this) {
            self::Normal => 'border-zinc-200 dark:border-zinc-600 bg-zinc-50/95 dark:bg-zinc-900/80',
            self::Low => 'border-sky-300/65 dark:border-sky-500/45 bg-sky-500/12 dark:bg-sky-400/12',
            self::High => 'border-amber-400/70 dark:border-amber-500/50 bg-amber-500/16 dark:bg-amber-400/14',
            self::Urgent => 'border-rose-400/75 dark:border-rose-500/50 bg-rose-500/18 dark:bg-rose-500/14',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
