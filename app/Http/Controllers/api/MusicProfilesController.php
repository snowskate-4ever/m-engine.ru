<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\UserMusicProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MusicProfilesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enabled = $this->normalizeProfiles($request->user()->music_profiles);

        return response()->json([
            'data' => [
                'enabled' => $enabled,
                'available' => $this->availableProfiles(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $availableKeys = collect($this->availableProfiles())
            ->pluck('key')
            ->all();

        $validated = $request->validate([
            'profile' => ['required', 'string', 'in:'.implode(',', $availableKeys)],
            'enabled' => ['required', 'boolean'],
        ]);

        $profile = (string) $validated['profile'];
        $enabledFlag = (bool) $validated['enabled'];
        $user = $request->user();

        $profiles = collect($this->normalizeProfiles($user->music_profiles));

        if ($enabledFlag) {
            $profiles->push($profile);
        } else {
            $profiles = $profiles->reject(fn (string $value) => $value === $profile)->values();
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();

        return response()->json([
            'ok' => true,
            'data' => [
                'enabled' => $this->normalizeProfiles($user->music_profiles),
                'available' => $this->availableProfiles(),
            ],
        ]);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function availableProfiles(): array
    {
        return [
            ['key' => UserMusicProfile::Musician->value, 'label' => (string) __('ui.public_profile.type_musician')],
            ['key' => UserMusicProfile::Teacher->value, 'label' => (string) __('ui.public_profile.type_teacher')],
            ['key' => UserMusicProfile::EventOrganizer->value, 'label' => (string) __('ui.music.profile_tab_organizer')],
            ['key' => UserMusicProfile::Manager->value, 'label' => (string) __('ui.music.profile_tab_manager')],
            ['key' => UserMusicProfile::SessionMusician->value, 'label' => (string) __('ui.music.profile_tab_session_musician')],
            ['key' => UserMusicProfile::Agent->value, 'label' => (string) __('ui.music.profile_tab_agent')],
            ['key' => UserMusicProfile::SoundEngineer->value, 'label' => (string) __('ui.music.profile_tab_sound_engineer')],
            ['key' => UserMusicProfile::Arranger->value, 'label' => (string) __('ui.music.profile_tab_arranger')],
            ['key' => UserMusicProfile::LiveSound->value, 'label' => (string) __('ui.music.profile_tab_live_sound')],
            ['key' => UserMusicProfile::LightingDesigner->value, 'label' => (string) __('ui.music.profile_tab_lighting_designer')],
            ['key' => UserMusicProfile::Videographer->value, 'label' => (string) __('ui.music.profile_tab_videographer')],
            ['key' => UserMusicProfile::Photographer->value, 'label' => (string) __('ui.music.profile_tab_photographer')],
            ['key' => UserMusicProfile::Journalist->value, 'label' => (string) __('ui.music.profile_tab_journalist')],
            ['key' => UserMusicProfile::VenueManager->value, 'label' => (string) __('ui.music.profile_tab_venue_manager')],
            ['key' => UserMusicProfile::Merchandiser->value, 'label' => (string) __('ui.music.profile_tab_merchandiser')],
            ['key' => UserMusicProfile::TourManager->value, 'label' => (string) __('ui.music.profile_tab_tour_manager')],
            ['key' => UserMusicProfile::Promoter->value, 'label' => (string) __('ui.music.profile_tab_promoter')],
            ['key' => UserMusicProfile::RecordingEngineer->value, 'label' => (string) __('ui.music.profile_tab_recording_engineer')],
            ['key' => UserMusicProfile::MasteringEngineer->value, 'label' => (string) __('ui.music.profile_tab_mastering_engineer')],
            ['key' => UserMusicProfile::SessionProducer->value, 'label' => (string) __('ui.music.profile_tab_session_producer')],
            ['key' => UserMusicProfile::TechRider->value, 'label' => (string) __('ui.music.profile_tab_tech_rider')],
            ['key' => UserMusicProfile::BacklineTech->value, 'label' => (string) __('ui.music.profile_tab_backline_tech')],
            ['key' => UserMusicProfile::GraphicDesigner->value, 'label' => (string) __('ui.music.profile_tab_graphic_designer')],
            ['key' => UserMusicProfile::SmmManager->value, 'label' => (string) __('ui.music.profile_tab_smm_manager')],
            ['key' => UserMusicProfile::MusicLawyer->value, 'label' => (string) __('ui.music.profile_tab_music_lawyer')],
            ['key' => UserMusicProfile::Accountant->value, 'label' => (string) __('ui.music.profile_tab_accountant')],
        ];
    }

    /**
     * Нормализует music_profiles из старого/нового формата в список ключей профилей.
     *
     * @param  mixed  $raw
     * @return list<string>
     */
    private function normalizeProfiles(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $keys = collect($this->availableProfiles())
            ->pluck('key')
            ->all();

        $result = [];
        foreach ($raw as $key => $value) {
            // Формат списка: ['musician', 'agent']
            if (is_int($key) && is_string($value) && $value !== '') {
                $result[] = $value;
                continue;
            }

            // Формат map: {'musician': true, 'agent': false}
            if (is_string($key) && in_array($key, $keys, true) && (bool) $value === true) {
                $result[] = $key;
            }
        }

        return array_values(array_unique($result));
    }
}
