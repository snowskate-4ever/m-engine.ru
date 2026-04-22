<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Enums\LegalDocumentVisibility;
use App\Models\ConcertVenue;
use App\Models\LegalDocument;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
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
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\DateTime;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\MorphTo;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<LegalDocument>
 */
final class LegalDocumentResource extends ModelResource
{
    protected string $model = LegalDocument::class;

    public function getTitle(): string
    {
        return 'Юридические документы';
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'title'),
            Text::make('Тип', fn (LegalDocument $d) => (string) $d->document_type?->value),
            Text::make('Статус', 'status')->badge(fn (mixed $v) => match ((string) $v) {
                LegalDocumentStatus::Approved->value => 'success',
                LegalDocumentStatus::PendingReview->value => 'warning',
                LegalDocumentStatus::Rejected->value => 'error',
                default => 'gray',
            }),
            Text::make('Видимость', fn (LegalDocument $d) => (string) $d->visibility?->value),
            DateTime::make('Проверено', 'reviewed_at')->format('d.m.Y H:i'),
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
                MorphTo::make('Владелец', 'owner')->types([
                    Musician::class => 'Музыкант',
                    Teacher::class => 'Преподаватель',
                    Peformer::class => 'Исполнитель',
                    Studio::class => 'Студия',
                    Rehersal::class => 'Репточка',
                    ConcertVenue::class => 'Концертная площадка',
                    School::class => 'Школа',
                    RecordLabel::class => 'Лейбл',
                    ProducerCenter::class => 'Продюсерский центр',
                    Shop::class => 'Магазин',
                ])->disabled(),
                Text::make('Название', 'title')->required(),
                Select::make('Тип', 'document_type')
                    ->options($this->typeOptions())
                    ->required(),
                Select::make('Статус', 'status')
                    ->options($this->statusOptions())
                    ->required(),
                Select::make('Видимость', 'visibility')
                    ->options($this->visibilityOptions())
                    ->required(),
                BelongsTo::make('Проверил', 'reviewer', formatted: static fn (User $u) => $u->email ?? (string) $u->name, resource: UserResource::class)
                    ->nullable(),
                DateTime::make('Проверено', 'reviewed_at')->nullable(),
                Textarea::make('Причина отклонения', 'rejection_reason')->nullable(),
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
        return ['id', 'title'];
    }

    public function rules(Model $item): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'in:'.implode(',', array_keys($this->typeOptions()))],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys($this->statusOptions()))],
            'visibility' => ['required', 'string', 'in:'.implode(',', array_keys($this->visibilityOptions()))],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $out = [];
        foreach (LegalDocumentType::cases() as $case) {
            $out[$case->value] = $case->value;
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        $out = [];
        foreach (LegalDocumentStatus::cases() as $case) {
            $out[$case->value] = $case->value;
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function visibilityOptions(): array
    {
        $out = [];
        foreach (LegalDocumentVisibility::cases() as $case) {
            $out[$case->value] = $case->value;
        }

        return $out;
    }
}
