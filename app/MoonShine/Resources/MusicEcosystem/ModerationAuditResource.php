<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Models\ModerationAudit;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<ModerationAudit>
 */
final class ModerationAuditResource extends ModelResource
{
    protected string $model = ModerationAudit::class;

    protected function isCan(Ability $ability): bool
    {
        if (in_array($ability, [Ability::CREATE, Ability::UPDATE, Ability::DELETE], true)) {
            return false;
        }

        return parent::isCan($ability);
    }

    public function getTitle(): string
    {
        return 'Аудит модерации';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc('created_at');
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Действие', 'action'),
            Text::make('Объект', fn (ModerationAudit $a) => class_basename((string) $a->auditable_type).' #'.$a->auditable_id),
            Text::make('Актор', fn (ModerationAudit $a) => $a->actor_type ? (class_basename((string) $a->actor_type).' #'.$a->actor_id) : '—'),
            Text::make('Время', 'created_at')->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return $this->detailFields();
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Действие', 'action'),
                Text::make('Тип объекта', 'auditable_type'),
                Text::make('ID объекта', 'auditable_id'),
                Text::make('Тип актора', 'actor_type')->nullable(),
                Text::make('ID актора', 'actor_id')->nullable(),
                Textarea::make('Было (JSON)', fn (ModerationAudit $a) => $a->old_values ? json_encode($a->old_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—'),
                Textarea::make('Стало (JSON)', fn (ModerationAudit $a) => $a->new_values ? json_encode($a->new_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—'),
                Text::make('Создано', 'created_at')->format('d.m.Y H:i:s'),
            ]),
        ];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\Crud\IndexPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function search(): array
    {
        return ['id', 'action'];
    }

    public function rules(Model $item): array
    {
        return [];
    }
}
