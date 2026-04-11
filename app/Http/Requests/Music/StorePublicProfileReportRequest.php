<?php

declare(strict_types=1);

namespace App\Http\Requests\Music;

use App\Models\Musician;
use App\Models\ConcertVenue;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicProfileReportRequest extends FormRequest
{
    /**
     * @return list<class-string>
     */
    public static function allowedReportableTypes(): array
    {
        return [
            Musician::class,
            Teacher::class,
            Peformer::class,
            Studio::class,
            Rehersal::class,
            ConcertVenue::class,
            School::class,
            RecordLabel::class,
            ProducerCenter::class,
            Shop::class,
        ];
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(self::allowedReportableTypes())],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('reportable_type');
            $id = (int) $this->input('reportable_id');
            if (! is_string($type) || $id < 1 || ! in_array($type, self::allowedReportableTypes(), true)) {
                return;
            }
            $model = $type::query()->find($id);
            if ($model === null) {
                $validator->errors()->add('reportable_id', __('validation.exists', ['attribute' => 'reportable_id']));

                return;
            }
            if (! (bool) ($model->public_page_enabled ?? false)) {
                $validator->errors()->add('reportable_id', __('ui.music.report_profile_not_public'));
            }
        });
    }
}
