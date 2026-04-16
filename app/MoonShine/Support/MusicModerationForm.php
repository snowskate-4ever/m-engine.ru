<?php

declare(strict_types=1);

namespace App\MoonShine\Support;

use App\Enums\ModerationStatus;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

final class MusicModerationForm
{
    /**
     * @return list<FieldContract>
     */
    public static function indexModerationColumn(): array
    {
        return [
            Text::make('Статус', 'moderation_status')->nullable(),
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
            Select::make('Статус модерации', 'moderation_status')
                ->options([
                    ModerationStatus::Approved->value => 'Одобрено',
                    ModerationStatus::Pending->value => 'На проверке',
                    ModerationStatus::Rejected->value => 'Отклонено',
                ])
                ->default(ModerationStatus::Approved->value),
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
            Select::make('Статус модерации', 'moderation_status')
                ->options([
                    ModerationStatus::Approved->value => 'Одобрено',
                    ModerationStatus::Pending->value => 'На проверке',
                    ModerationStatus::Rejected->value => 'Отклонено',
                ])
                ->default(ModerationStatus::Approved->value),
            Date::make('Скрыто модерацией с', 'moderation_hidden_at')->withTime()->nullable(),
            Textarea::make('Причина (внутр.)', 'moderation_reason')->nullable(),
            Date::make('Запрошена проверка с', 'moderation_review_requested_at')->withTime()->nullable(),
        ];
    }
}
