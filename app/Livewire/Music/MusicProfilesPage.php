<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Models\User;
use App\Services\Music\MusicActorContextService;
use App\Support\Music\MusicProfileCriteriaMatrix;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class MusicProfilesPage extends Component
{
    /**
     * @var 'musician'|'teacher'|'organizer'|'manager'|'session_musician'|'agent'|'sound_engineer'|'arranger'|'live_sound'|'lighting_designer'|'videographer'|'photographer'|'journalist'|'venue_manager'|'merchandiser'|'tour_manager'|'promoter'|'recording_engineer'|'mastering_engineer'|'session_producer'|'tech_rider'|'backline_tech'|'graphic_designer'|'smm_manager'|'music_lawyer'|'accountant'
     */
    #[Url(history: true)]
    public string $tab = 'musician';

    /**
     * Переключатель между включёнными профилями (только включённые в UI).
     */
    public string $quickSwitchTab = 'musician';

    public ?string $activeActorRef = null;

    /**
     * Инкрементируем при смене профиля, чтобы дочерний компонент точно пересобрался с сервера.
     */
    public int $profileRequestVersion = 1;

    public function mount(): void
    {
        if (! in_array($this->tab, $this->allowedTabs(), true)) {
            $this->tab = 'musician';
        }

        /** @var User $user */
        $user = Auth::user();
        $enabled = $this->enabledTabKeys($user);
        if ($enabled !== [] && ! $this->tabIsEnabled($user, $this->tab)) {
            $this->tab = $enabled[0];
        }

        $this->syncQuickSwitchFromUser($user);

        $current = app(MusicActorContextService::class)->currentActor(Auth::user());
        if ($current !== null) {
            $this->activeActorRef = $current['type'].':'.$current['id'];
        }
    }

    public function updatedTab(string $value): void
    {
        if (! in_array($value, $this->allowedTabs(), true)) {
            return;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $enabled = $this->enabledTabKeys($user);
        if ($enabled !== [] && in_array($value, $enabled, true)) {
            $this->quickSwitchTab = $value;
        }

        $this->profileRequestVersion++;
    }

    public function updatedQuickSwitchTab(string $value): void
    {
        $this->switchProfile($value);
    }

    public function switchProfile(string $value): void
    {
        if (! in_array($value, $this->allowedTabs(), true)) {
            return;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $enabled = $this->enabledTabKeys($user);
        if ($enabled !== [] && ! in_array($value, $enabled, true)) {
            return;
        }

        $this->quickSwitchTab = $value;
        $this->tab = $value;
        $this->profileRequestVersion++;
    }

    public function saveActiveActor(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->activeActorRef === null || $this->activeActorRef === '') {
            $user->setActiveMusicActor(null, null);
            session()->flash('success', __('ui.music.saved'));

            return;
        }

        [$type, $id] = explode(':', $this->activeActorRef, 2);
        app(MusicActorContextService::class)->setActiveActor($user, (string) $type, (int) $id);
        session()->flash('success', __('ui.music.saved'));
    }

    #[On('music-profiles-updated')]
    public function onMusicProfilesUpdated(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $user->refresh();

        $enabled = $this->enabledTabKeys($user);
        if ($enabled !== [] && ! $this->tabIsEnabled($user, $this->tab)) {
            $this->tab = $enabled[0];
        }

        $this->syncQuickSwitchFromUser($user);
        $this->profileRequestVersion++;
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $actorOptions = app(MusicActorContextService::class)->availableActors($user);
        $labels = $this->tabLabels();

        $enabledKeys = $this->enabledTabKeys($user);
        $hasAnyEnabled = $enabledKeys !== [];

        $enabledTabOptions = array_map(
            static fn (string $tab): array => ['value' => $tab, 'label' => $labels[$tab] ?? $tab],
            $enabledKeys
        );

        return view('livewire.music.music-profiles-page', [
            'actorOptions' => $actorOptions,
            'hasAnyEnabledProfile' => $hasAnyEnabled,
            'enabledTabOptions' => $enabledTabOptions,
        ]);
    }

    /**
     * @return list<string>
     */
    private function allowedTabs(): array
    {
        return [
            'musician',
            'teacher',
            'organizer',
            'manager',
            'session_musician',
            'agent',
            'sound_engineer',
            'arranger',
            'live_sound',
            'lighting_designer',
            'videographer',
            'photographer',
            'journalist',
            'venue_manager',
            'merchandiser',
            'tour_manager',
            'promoter',
            'recording_engineer',
            'mastering_engineer',
            'session_producer',
            'tech_rider',
            'backline_tech',
            'graphic_designer',
            'smm_manager',
            'music_lawyer',
            'accountant',
        ];
    }

    /**
     * @return list<string>
     */
    private function enabledTabKeys(User $user): array
    {
        return array_values(array_filter(
            $this->allowedTabs(),
            fn (string $tab): bool => $this->tabIsEnabled($user, $tab)
        ));
    }

    private function tabIsEnabled(User $user, string $tab): bool
    {
        $profile = MusicProfileCriteriaMatrix::profileFromTab($tab);

        return $profile !== null && $user->hasMusicProfile($profile);
    }

    private function syncQuickSwitchFromUser(User $user): void
    {
        $enabled = $this->enabledTabKeys($user);
        if ($enabled === []) {
            $this->quickSwitchTab = $this->tab;

            return;
        }

        if (in_array($this->tab, $enabled, true)) {
            $this->quickSwitchTab = $this->tab;
        } else {
            $this->quickSwitchTab = $enabled[0];
        }
    }

    /**
     * @return array<string, string>
     */
    private function tabLabels(): array
    {
        return [
            'musician' => (string) __('ui.public_profile.type_musician'),
            'teacher' => (string) __('ui.public_profile.type_teacher'),
            'organizer' => (string) __('ui.music.profile_tab_organizer'),
            'manager' => (string) __('ui.music.profile_tab_manager'),
            'session_musician' => (string) __('ui.music.profile_tab_session_musician'),
            'agent' => (string) __('ui.music.profile_tab_agent'),
            'sound_engineer' => (string) __('ui.music.profile_tab_sound_engineer'),
            'arranger' => (string) __('ui.music.profile_tab_arranger'),
            'live_sound' => (string) __('ui.music.profile_tab_live_sound'),
            'lighting_designer' => (string) __('ui.music.profile_tab_lighting_designer'),
            'videographer' => (string) __('ui.music.profile_tab_videographer'),
            'photographer' => (string) __('ui.music.profile_tab_photographer'),
            'journalist' => (string) __('ui.music.profile_tab_journalist'),
            'venue_manager' => (string) __('ui.music.profile_tab_venue_manager'),
            'merchandiser' => (string) __('ui.music.profile_tab_merchandiser'),
            'tour_manager' => (string) __('ui.music.profile_tab_tour_manager'),
            'promoter' => (string) __('ui.music.profile_tab_promoter'),
            'recording_engineer' => (string) __('ui.music.profile_tab_recording_engineer'),
            'mastering_engineer' => (string) __('ui.music.profile_tab_mastering_engineer'),
            'session_producer' => (string) __('ui.music.profile_tab_session_producer'),
            'tech_rider' => (string) __('ui.music.profile_tab_tech_rider'),
            'backline_tech' => (string) __('ui.music.profile_tab_backline_tech'),
            'graphic_designer' => (string) __('ui.music.profile_tab_graphic_designer'),
            'smm_manager' => (string) __('ui.music.profile_tab_smm_manager'),
            'music_lawyer' => (string) __('ui.music.profile_tab_music_lawyer'),
            'accountant' => (string) __('ui.music.profile_tab_accountant'),
        ];
    }
}
