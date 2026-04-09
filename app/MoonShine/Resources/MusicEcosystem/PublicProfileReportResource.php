<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Enums\PublicProfileReportStatus;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\PublicProfileReport;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use App\Models\User;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\MorphTo;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<PublicProfileReport>
 */
final class PublicProfileReportResource extends ModelResource
{
    protected string $model = PublicProfileReport::class;

    protected function isCan(Ability $ability): bool
    {
        if ($ability === Ability::CREATE) {
            return false;
        }

        return parent::isCan($ability);
    }

    public function getTitle(): string
    {
        return 'Жалобы на публичные профили';
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        $out = [];
        foreach (PublicProfileReportStatus::cases() as $case) {
            $out[$case->value] = match ($case) {
                PublicProfileReportStatus::Pending => 'Новая',
                PublicProfileReportStatus::Reviewed => 'Рассмотрена',
                PublicProfileReportStatus::Dismissed => 'Отклонена',
            };
        }

        return $out;
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Статус', 'status')
                ->badge(fn (mixed $v) => match ((string) $v) {
                    PublicProfileReportStatus::Pending->value => 'warning',
                    PublicProfileReportStatus::Reviewed->value => 'success',
                    PublicProfileReportStatus::Dismissed->value => 'gray',
                    default => 'gray',
                }),
            BelongsTo::make(
                'Автор',
                'reporter',
                formatted: static fn (User $u) => $u->email ?? (string) $u->name,
                resource: UserResource::class,
            )->searchable(),
            Text::make('Объект', function (PublicProfileReport $r) {
                $m = $r->reportable;

                return $m ? ($m::class.' #'.$m->getKey()) : '—';
            }),
            Text::make('Создан', 'created_at')->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make([
                ID::make()->disabled(),
                BelongsTo::make(
                    'Автор',
                    'reporter',
                    formatted: static fn (User $u) => $u->email ?? (string) $u->name,
                    resource: UserResource::class,
                )->disabled(),
                MorphTo::make('Объект', 'reportable')
                    ->types([
                        Musician::class => 'Музыкант',
                        Teacher::class => 'Преподаватель',
                        Peformer::class => 'Исполнитель',
                        Studio::class => 'Студия',
                        Rehersal::class => 'Репточка',
                        School::class => 'Школа',
                        RecordLabel::class => 'Лейбл',
                        ProducerCenter::class => 'Продюсерский центр',
                        Shop::class => 'Магазин',
                    ])
                    ->disabled(),
                Textarea::make('Текст жалобы', 'reason')->disabled(),
                Select::make('Статус', 'status')
                    ->options(self::statusOptions())
                    ->required(),
                Textarea::make('Заметки модератора', 'admin_notes')->nullable(),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return $this->formFields();
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\Crud\IndexPage::class,
            \MoonShine\Laravel\Pages\Crud\FormPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function search(): array
    {
        return ['id'];
    }

    public function rules(Model $item): array
    {
        $statuses = array_map(static fn (PublicProfileReportStatus $c) => $c->value, PublicProfileReportStatus::cases());

        return [
            'status' => ['required', 'in:'.implode(',', $statuses)],
            'admin_notes' => ['nullable', 'string'],
        ];
    }
}
