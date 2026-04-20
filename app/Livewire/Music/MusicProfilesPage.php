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

    public ?string $activeActorRef = null;

    public function mount(): void
    {
        if (! in_array($this->tab, $this->allowedTabs(), true)) {
            $this->tab = 'musician';
        }

        /** @var User $user */
        $user = Auth::user();
        $selectable = $this->tabsForSelect($user);
        if (! in_array($this->tab, $selectable, true)) {
            $this->tab = $selectable[0] ?? 'musician';
        }

        $current = app(MusicActorContextService::class)->currentActor(Auth::user());
        if ($current !== null) {
            $this->activeActorRef = $current['type'].':'.$current['id'];
        }
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

        $selectable = $this->tabsForSelect($user);
        if (! in_array($this->tab, $selectable, true)) {
            $this->tab = $selectable[0] ?? 'musician';
        }
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $actorOptions = app(MusicActorContextService::class)->availableActors($user);
        $labels = $this->tabLabels();
        $tabOptions = array_map(
            static fn (string $tab): array => [
                'value' => $tab,
                'label' => $labels[$tab] ?? $tab,
            ],
            $this->tabsForSelect($user)
        );

        return view('livewire.music.music-profiles-page', [
            'actorOptions' => $actorOptions,
            'profileDescription' => $this->profileDescription($this->tab),
            'tabOptions' => $tabOptions,
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

    private function profileDescription(string $tab): string
    {
        return (string) __('ui.music.profile_description_'.$tab);
    }

    /**
     * @return list<string>
     */
    private function tabsForSelect(User $user): array
    {
        $enabled = array_values(array_filter(
            $this->allowedTabs(),
            fn (string $tab): bool => $this->tabIsEnabled($user, $tab)
        ));

        return $enabled !== [] ? $enabled : $this->allowedTabs();
    }

    private function tabIsEnabled(User $user, string $tab): bool
    {
        $profile = MusicProfileCriteriaMatrix::profileFromTab($tab);

        return $profile !== null && $user->hasMusicProfile($profile);
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
