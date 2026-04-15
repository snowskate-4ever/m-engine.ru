<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Livewire\Music\MusicianProfilePage;
use App\Livewire\Music\TeacherProfilePage;
use App\Models\City;
use App\Models\Country;
use App\Models\Genre;
use App\Models\Instrument;
use App\Models\Musician;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MusicProfileEnablementTest extends TestCase
{
    use RefreshDatabase;

    public function test_musician_profile_is_disabled_by_default_and_can_be_enabled(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        Livewire::actingAs($user)->test(MusicianProfilePage::class)
            ->assertSet('enabled', false)
            ->call('toggleProfile')
            ->assertSet('enabled', true);

        $this->assertTrue($user->fresh()->hasMusicProfile('musician'));
    }

    public function test_teacher_profile_is_disabled_by_default_and_can_be_enabled(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        Livewire::actingAs($user)->test(TeacherProfilePage::class)
            ->assertSet('enabled', false)
            ->call('toggleProfile')
            ->assertSet('enabled', true);

        $this->assertTrue($user->fresh()->hasMusicProfile('teacher'));
        $this->assertNotNull(Teacher::query()->where('user_id', $user->id)->first());
    }

    public function test_musician_profile_save_requires_enabled_profile(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        Livewire::actingAs($user)->test(MusicianProfilePage::class)
            ->set('name', 'Blocked Musician')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_enabled_teacher_profile_creates_record_on_mount(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['teacher'],
        ]);

        $this->assertNull(Teacher::query()->where('user_id', $user->id)->first());

        Livewire::actingAs($user)->test(TeacherProfilePage::class)
            ->assertSet('enabled', true);

        $this->assertNotNull(Teacher::query()->where('user_id', $user->id)->first());
    }

    public function test_enabled_musician_profile_creates_record_on_mount(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['musician'],
        ]);

        $this->assertNull(Musician::query()->where('user_id', $user->id)->first());

        Livewire::actingAs($user)->test(MusicianProfilePage::class)
            ->assertSet('enabled', true);

        $this->assertNotNull(Musician::query()->where('user_id', $user->id)->first());
    }

    public function test_disabled_teacher_profile_also_creates_record_on_mount(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        $this->assertNull(Teacher::query()->where('user_id', $user->id)->first());

        Livewire::actingAs($user)->test(TeacherProfilePage::class)
            ->assertSet('enabled', false);

        $this->assertNotNull(Teacher::query()->where('user_id', $user->id)->first());
    }

    public function test_disabled_musician_profile_also_creates_record_on_mount(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        $this->assertNull(Musician::query()->where('user_id', $user->id)->first());

        Livewire::actingAs($user)->test(MusicianProfilePage::class)
            ->assertSet('enabled', false);

        $this->assertNotNull(Musician::query()->where('user_id', $user->id)->first());
    }

    public function test_musician_save_stores_genres_cities_and_experience(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        try {
            $instrument = Instrument::query()->create([
                'name' => 'Seeded Instrument '.Str::random(8),
                'description' => null,
                'active' => true,
                'sort_order' => 1,
            ]);
            $genre = Genre::query()->create([
                'name' => 'Seeded Genre '.Str::random(8),
                'description' => null,
                'active' => true,
                'sort_order' => 1,
            ]);
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
                'name' => 'Test City',
                'name_eng' => 'Test City',
                'slug' => 'test-city-'.Str::lower(Str::random(10)),
                'timezone' => 'UTC',
                'latitude' => 0,
                'longitude' => 0,
                'population' => 1000,
                'sort_order' => 0,
                'is_capital' => false,
                'is_active' => true,
            ]);

            $user = User::factory()->create([
                'music_profiles' => ['musician'],
            ]);

            Livewire::actingAs($user)->test(MusicianProfilePage::class)
                ->set('instrumentIds', [$instrument->id])
                ->set('genreIds', [$genre->id])
                ->set('cityIds', [$city->id])
                ->set('cityPickerCountryId', $country->id)
                ->set('experienceStartMonth', 4)
                ->set('experienceStartYear', 2021)
                ->set('name', 'Performer One')
                ->call('save')
                ->assertHasNoErrors();

            $m = Musician::query()->where('user_id', $user->id)->first();
            $this->assertNotNull($m);
            $this->assertSame('2021-04-01', $m->experience_started_on?->format('Y-m-d'));
            $this->assertSame(5, (int) $m->years_of_experience);
            $this->assertTrue($m->genres()->whereKey($genre->id)->exists());
            $this->assertTrue($m->cities()->whereKey($city->id)->exists());
        } finally {
            Carbon::setTestNow();
        }
    }
}
