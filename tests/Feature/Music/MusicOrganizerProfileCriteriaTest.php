<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\UserMusicProfile;
use App\Livewire\Music\MusicUserJsonCriteriaForm;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MusicOrganizerProfileCriteriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_save_stores_cities_and_experience_in_user_json(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        try {
            $country = Country::query()->create([
                'name' => 'Test Country '.Str::random(6),
                'code' => Str::upper(Str::random(2)),
                'phone_code' => '+1',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'sort_order' => 1,
                'is_active' => true,
            ]);
            $city = City::query()->create([
                'region_id' => null,
                'country_id' => $country->id,
                'name' => 'Test City Org',
                'name_eng' => 'Test City Org',
                'slug' => 'test-city-org-'.Str::lower(Str::random(10)),
                'timezone' => 'UTC',
                'latitude' => 0,
                'longitude' => 0,
                'population' => 1000,
                'sort_order' => 0,
                'is_capital' => false,
                'is_active' => true,
            ]);

            $user = User::factory()->create([
                'music_profiles' => [UserMusicProfile::EventOrganizer->value],
            ]);

            Livewire::actingAs($user)->test(MusicUserJsonCriteriaForm::class, [
                'profileKey' => UserMusicProfile::EventOrganizer->value,
                'enabled' => true,
            ])
                ->set('cityPickerCountryId', $country->id)
                ->set('cityIds', [$city->id])
                ->set('experienceStartMonth', 4)
                ->set('experienceStartYear', 2021)
                ->call('save')
                ->assertHasNoErrors();

            $user->refresh();
            $bucket = $user->musicProfileCriteriaFor(UserMusicProfile::EventOrganizer->value);
            $this->assertSame([$city->id], array_map('intval', $bucket['cities'] ?? []));
            $this->assertSame('2021-04-01', $bucket['experience_started_on'] ?? null);
        } finally {
            Carbon::setTestNow();
        }
    }
}
