<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Textarea;

final class MusicModerationForm
{
    /**
     * @return list<FieldContract>
     */
    public static function indexModerationColumn(): array
    {
        return [
            Date::make('Модерация (скрыто с)', 'moderation_hidden_at')
                ->format('d.m.Y H:i')
                ->nullable(),
            Date::make('Запрос проверки', 'moderation_review_requested_at')
                ->format('d.m.Y H:i')
                ->nullable(),
        ];
    }

    /**
     * Поля комиссии и модерации для магазина (внутри общего Box формы).
     *
     * @return list<FieldContract>
     */
    public static function shopFields(): array
    {
        return [
            Number::make('Комиссия платформы (доля, 0.01 = 1%)', 'platform_fee_rate')
                ->step(0.0001)
                ->min(0)
                ->max(1)
                ->nullable()
                ->hint('Пусто — из config/shop.php'),
            Date::make('Скрыто модерацией с', 'moderation_hidden_at')->withTime()->nullable(),
            Textarea::make('Причина (внутр.)', 'moderation_reason')->nullable(),
            Date::make('Запрошена проверка с', 'moderation_review_requested_at')->withTime()->nullable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    public static function profileFields(): array
    {
        return [
            Date::make('Скрыто модерацией с', 'moderation_hidden_at')->withTime()->nullable(),
            Textarea::make('Причина (внутр.)', 'moderation_reason')->nullable(),
            Date::make('Запрошена проверка с', 'moderation_review_requested_at')->withTime()->nullable(),
        ];
    }
}
