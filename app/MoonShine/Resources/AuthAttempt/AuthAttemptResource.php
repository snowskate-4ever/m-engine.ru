<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AuthAttempt;

use Illuminate\Database\Eloquent\Model;
use App\Models\AuthAttempt;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\BelongsTo;
use MoonShine\UI\Fields\Json;
use MoonShine\Contracts\UI\FieldContract;

/**
 * @extends ModelResource<AuthAttempt>
 */
class AuthAttemptResource extends ModelResource
{
    protected string $model = AuthAttempt::class;

    public function getTitle(): string
    {
        return 'Попытки авторизации';
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Канал', 'channel'),
            Enum::make('Тип канала', 'channel_type')
                ->attach(AuthAttemptResource::class, 'channelTypes'),
            Enum::make('Статус', 'status')
                ->attach(AuthAttemptResource::class, 'statuses'),
            Text::make('IP адрес', 'ip_address'),
            BelongsTo::make('Пользователь', 'user', 'name', resource: 'App\MoonShine\Resources\User\UserResource'),
            Date::make('Создано', 'created_at')->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make('Основная информация', [
                ID::make(),
                Text::make('Канал', 'channel'),
                Enum::make('Тип канала', 'channel_type')
                    ->attach(AuthAttemptResource::class, 'channelTypes')
                    ->required(),
                Enum::make('Статус', 'status')
                    ->attach(AuthAttemptResource::class, 'statuses')
                    ->required(),
                Text::make('IP адрес', 'ip_address'),
                Text::make('User Agent', 'user_agent'),
                BelongsTo::make('Пользователь', 'user', 'name', resource: 'App\MoonShine\Resources\User\UserResource'),
                Text::make('Токен', 'auth_token'),
                Date::make('Истекает', 'expires_at'),
                Json::make('Метаданные', 'metadata'),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make('Информация о попытке', [
                ID::make(),
                Text::make('Канал', 'channel'),
                Enum::make('Тип канала', 'channel_type')
                    ->attach(AuthAttemptResource::class, 'channelTypes'),
                Enum::make('Статус', 'status')
                    ->attach(AuthAttemptResource::class, 'statuses'),
                Text::make('IP адрес', 'ip_address'),
                Text::make('User Agent', 'user_agent'),
                BelongsTo::make('Пользователь', 'user', 'name', resource: 'App\MoonShine\Resources\User\UserResource'),
                Text::make('Токен', 'auth_token'),
                Date::make('Истекает', 'expires_at'),
                Json::make('Метаданные', 'metadata'),
                Date::make('Создано', 'created_at'),
                Date::make('Обновлено', 'updated_at'),
            ]),
        ];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\IndexPage::class,
            \MoonShine\Laravel\Pages\FormPage::class,
            \MoonShine\Laravel\Pages\DetailPage::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function channelTypes(): array
    {
        return [
            'telegram' => 'Telegram',
            'web' => 'Веб-сайт',
            'api' => 'API',
            'n8n_webhook' => 'N8N Webhook',
            'other' => 'Другое',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'pending' => 'Ожидает',
            'success' => 'Успешно',
            'failed' => 'Неудачно',
            'expired' => 'Истекло',
        ];
    }

    public function search(): array
    {
        return ['channel', 'channel_type', 'status', 'ip_address'];
    }

    public function filters(): array
    {
        return [
            Enum::make('Тип канала', 'channel_type')
                ->options(self::channelTypes()),
            Enum::make('Статус', 'status')
                ->options(self::statuses()),
        ];
    }

    public function getActiveActions(): array
    {
        return ['view', 'delete']; // Только просмотр и удаление, без редактирования
    }
}