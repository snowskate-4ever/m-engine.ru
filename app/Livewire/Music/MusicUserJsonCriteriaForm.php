<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\UserMusicProfile;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MusicUserJsonCriteriaForm extends Component
{
    public string $profileKey = '';

    public bool $enabled = true;

    /** @var list<int> */
    public array $cityIds = [];

    public ?int $selectedCityId = null;

    public int $cityPickerCountryId = 0;

    public ?int $experienceStartMonth = null;

    public ?int $experienceStartYear = null;

    public function mount(string $profileKey, bool $enabled = true): void
    {
        $this->profileKey = $profileKey;
        $this->enabled = $enabled;

        /** @var User $user */
        $user = Auth::user();

        if (UserMusicProfile::tryFrom($this->profileKey) === UserMusicProfile::Teacher && $user->teacher !== null) {
            $user->teacher->loadMissing('cities');
            $this->cityIds = $user->teacher->cities->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
        } else {
            $bucket = $user->musicProfileCriteriaFor($this->profileKey);
            $cities = $bucket['cities'] ?? [];
            if (is_array($cities)) {
                $this->cityIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $cities)));
            }
        }

        $bucket = $user->musicProfileCriteriaFor($this->profileKey);
        $exp = $bucket['experience_started_on'] ?? null;
        if (is_string($exp) && $exp !== '') {
            $d = CarbonImmutable::parse($exp);
            $this->experienceStartMonth = (int) $d->format('n');
            $this->experienceStartYear = (int) $d->format('Y');
        }

        $this->cityPickerCountryId = (int) (
            Country::query()->where('code', 'RU')->value('id')
            ?? Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->value('id')
            ?? Country::query()->orderBy('sort_order')->orderBy('name')->value('id')
            ?? 0
        );
    }

    public function save(): void
    {
        if (! $this->enabled) {
            $this->addError('cityIds', __('ui.music.profile_enable_required'));

            return;
        }

        if (UserMusicProfile::tryFrom($this->profileKey) === null) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $this->experienceStartMonth = $this->experienceStartMonth === '' || $this->experienceStartMonth === false
            ? null
            : ($this->experienceStartMonth !== null ? (int) $this->experienceStartMonth : null);
        $this->experienceStartYear = $this->experienceStartYear === '' || $this->experienceStartYear === false
            ? null
            : ($this->experienceStartYear !== null ? (int) $this->experienceStartYear : null);

        $this->cityIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $this->cityIds)));

        $validated = $this->validate([
            'cityIds' => ['nullable', 'array'],
            'cityIds.*' => ['integer', 'exists:cities,id'],
            'experienceStartMonth' => ['nullable', 'integer', 'between:1,12', 'required_with:experienceStartYear'],
            'experienceStartYear' => [
                'nullable',
                'integer',
                'min:'.(now()->year - 80),
                'max:'.now()->year,
                'required_with:experienceStartMonth',
            ],
        ]);

        $experienceStartedOn = null;
        if (($validated['experienceStartMonth'] ?? null) !== null && ($validated['experienceStartYear'] ?? null) !== null) {
            $start = CarbonImmutable::parse(sprintf(
                '%04d-%02d-01',
                (int) $validated['experienceStartYear'],
                (int) $validated['experienceStartMonth'],
            ))->startOfMonth();
            $latestAllowed = now()->startOfMonth();
            if ($start->greaterThan($latestAllowed)) {
                throw ValidationException::withMessages([
                    'experienceStartMonth' => [__('ui.music.validation.experience_started_future')],
                ]);
            }
            $oldestAllowed = now()->subYears(80)->startOfMonth();
            if ($start->lessThan($oldestAllowed)) {
                throw ValidationException::withMessages([
                    'experienceStartYear' => [__('ui.music.validation.experience_started_too_old', [
                        'year' => $oldestAllowed->year,
                    ])],
                ]);
            }
            $experienceStartedOn = $start->toDateString();
        }

        $cityIds = array_values($validated['cityIds'] ?? []);

        $user->mergeMusicProfileCriteria($this->profileKey, [
            'cities' => $cityIds,
            'experience_started_on' => $experienceStartedOn,
        ]);
        $user->save();

        if (UserMusicProfile::tryFrom($this->profileKey) === UserMusicProfile::Teacher && $user->teacher !== null) {
            $user->teacher->cities()->sync($cityIds);
        }

        session()->flash('success', __('ui.music.saved'));
    }

    public function addCity(): void
    {
        if ($this->selectedCityId === null) {
            return;
        }

        $cityId = (int) $this->selectedCityId;
        $exists = City::query()
            ->whereKey($cityId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            return;
        }

        if (! in_array($cityId, $this->cityIds, true)) {
            $this->cityIds[] = $cityId;
            sort($this->cityIds);
        }

        $this->selectedCityId = null;
    }

    public function removeCity(int $cityId): void
    {
        $this->cityIds = array_values(array_filter(
            $this->cityIds,
            static fn (int $id): bool => $id !== $cityId
        ));
    }

    public function render(): View
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cityPickerCities = collect();
        if ($this->cityPickerCountryId > 0) {
            $cityPickerCities = City::query()
                ->where('country_id', $this->cityPickerCountryId)
                ->where('is_active', true)
                ->orderByDesc('is_capital')
                ->orderByDesc('population')
                ->orderBy('name')
                ->limit(500)
                ->get();
        }

        $pickedCities = $this->cityIds !== []
            ? City::query()->whereIn('id', $this->cityIds)->orderBy('name')->get()
            : collect();

        $experienceMonthOptions = [];
        foreach (range(1, 12) as $m) {
            $experienceMonthOptions[] = [
                'value' => $m,
                'label' => CarbonImmutable::parse(sprintf('2000-%02d-01', $m))
                    ->locale(app()->getLocale())
                    ->translatedFormat('F'),
            ];
        }
        $y0 = (int) now()->year;
        $experienceYearOptions = range($y0, $y0 - 80);

        return view('livewire.music.music-user-json-criteria-form', [
            'countries' => $countries,
            'cityPickerCities' => $cityPickerCities,
            'pickedCities' => $pickedCities,
            'experienceMonthOptions' => $experienceMonthOptions,
            'experienceYearOptions' => $experienceYearOptions,
        ]);
    }
}
