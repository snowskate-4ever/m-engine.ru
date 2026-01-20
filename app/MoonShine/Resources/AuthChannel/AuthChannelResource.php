<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AuthChannel;

use Illuminate\Database\Eloquent\Model;
use App\Models\AuthChannel;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Url;
use MoonShine\UI\Fields\Json;
use MoonShine\Contracts\UI\FieldContract;

/**
 * @extends ModelResource<AuthChannel>
 */
class AuthChannelResource extends ModelResource
{
    protected string $model = AuthChannel::class;

    public function getTitle(): string
    {
        return 'Каналы авторизации';
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
            Text::make('Тип', 'type'),
            Switcher::make('Активен', 'is_active'),
            Url::make('Webhook URL', 'webhook_url'),
            Text::make('Создан', 'created_at')->format('d.m.Y H:i'),
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
                Text::make('Название', 'name')
                    ->required()
                    ->unique(ignoreOnUpdate: true),
                Text::make('Тип', 'type')
                    ->required()
                    ->placeholder('telegram, web, api, n8n_webhook'),
                Switcher::make('Активен', 'is_active'),
                Url::make('Webhook URL', 'webhook_url')
                    ->nullable(),
                Json::make('Конфигурация', 'config')
                    ->nullable()
                    ->hint('JSON конфигурация канала авторизации'),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make('Информация о канале', [
                ID::make(),
                Text::make('Название', 'name'),
                Text::make('Тип', 'type'),
                Switcher::make('Активен', 'is_active'),
                Url::make('Webhook URL', 'webhook_url'),
                Json::make('Конфигурация', 'config'),
                Text::make('Создан', 'created_at'),
                Text::make('Обновлен', 'updated_at'),
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

    public function search(): array
    {
        return ['name', 'type'];
    }

    public function filters(): array
    {
        return [
            Text::make('Тип', 'type'),
            Switcher::make('Активен', 'is_active'),
        ];
    }

    public function rules(Model $item): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:auth_channels,name,' . $item->getKey()],
            'type' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'config' => ['nullable', 'json'],
        ];
    }
}