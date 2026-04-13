<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Livewire\Music\MusicianProfilePage;
use App\Livewire\Music\TeacherProfilePage;
use App\Models\Musician;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
