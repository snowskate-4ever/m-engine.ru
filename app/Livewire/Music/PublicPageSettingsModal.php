<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\UserMusicProfile;
use App\Models\ConcertVenue;
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
use App\Support\Music\PublicProfileBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicPageSettingsModal extends Component
{
    /**
     * user_profiles — только роли (music_profiles); public_pages — публичные страницы сущностей без user_profile:*.
     *
     * @var 'user_profiles'|'public_pages'
     */
    public string $panel = 'public_pages';

    /** @var array<string, array{key: string, id: int, type: string, label: string, name: string, slug: string, enabled: bool}> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $slugs = [];

    /** @var array<string, bool> */
    public array $enabled = [];

    /** @var array<string, array<string, bool>> */
    public array $layoutBlockEnabled = [];

    /** @var array<string, string> */
    public array $selectedLayoutBlockId = [];

    public ?string $savedKey = null;

    /** @var array<string, bool> */
    public array $profileEnabled = [];

    public function mount(): void
    {
        if (! in_array($this->panel, ['user_profiles', 'public_pages'], true)) {
            $this->panel = 'public_pages';
        }

        $this->reloadRows();
    }

    /**
     * Keep `$this->rows` in sync with checkbox bindings so `sortedRows()` reflects the UI.
     */
    public function updated(string $fullPath, mixed $newValue): void
    {
        if (str_starts_with($fullPath, 'enabled.')) {
            $key = substr($fullPath, strlen('enabled.'));
            if ($key !== '' && isset($this->rows[$key])) {
                $row = $this->rows[$key];
                $row['enabled'] = $this->normalizeBool($newValue);
                $this->rows[$key] = $row;
            }
        }

        if (str_starts_with($fullPath, 'profileEnabled.')) {
            $key = substr($fullPath, strlen('profileEnabled.'));
            if ($key !== '' && isset($this->rows[$key])) {
                $row = $this->rows[$key];
                $row['profile_enabled'] = $this->normalizeBool($newValue);
                $this->rows[$key] = $row;
            }
        }
    }

    public function saveRow(string $key): void
    {
        if (str_starts_with($key, 'user_profile:')) {
            $this->saveUserProfileRow($key);

            return;
        }

        $model = $this->resolveOwnedModelByKey($key);
        if (! $model) {
            return;
        }

        $type = $this->extractTypeFromKey($key);
        if ($type === null) {
            return;
        }

        $slug = trim((string) ($this->slugs[$key] ?? ''));
        $isEnabled = $this->normalizeBool($this->enabled[$key] ?? false);

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique($this->slugTableByType($type), 'slug')->ignore($model->getKey()),
        ];
        if ($isEnabled) {
            $slugRules[0] = 'required';
        }

        $this->validate([
            "slugs.{$key}" => $slugRules,
            "enabled.{$key}" => ['boolean'],
        ], [
            "slugs.{$key}.required" => __('ui.music.validation.slug_required_public'),
            "slugs.{$key}.regex" => __('ui.music.validation.slug_format'),
        ]);

        $model->update([
            'slug' => $slug !== '' ? $slug : null,
            'public_page_enabled' => $isEnabled,
            'layout_draft' => PublicProfileBlocks::wrapVersion1($this->buildLayoutDraftBlocks($type, $key)),
        ]);

        $this->savedKey = $key;
        $this->reloadRows();
    }

    public function toggleUserProfileRow(string $key): void
    {
        if ($this->panel !== 'user_profiles') {
            return;
        }

        if (! str_starts_with($key, 'user_profile:')) {
            return;
        }

        $profile = $this->extractUserProfileFromKey($key);
        if ($profile === null) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        $user->refresh();

        $this->applyUserProfileMembership($user, $profile, ! $user->hasMusicProfile($profile));

        $this->savedKey = $key;
        $this->reloadRows();
    }

    private function saveUserProfileRow(string $key): void
    {
        if ($this->panel !== 'user_profiles') {
            return;
        }

        $profile = $this->extractUserProfileFromKey($key);
        if ($profile === null) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $this->applyUserProfileMembership($user, $profile, $this->normalizeBool($this->profileEnabled[$key] ?? false));

        $this->savedKey = $key;
        $this->reloadRows();
    }

    private function applyUserProfileMembership(User $user, UserMusicProfile $profile, bool $enabled): void
    {
        $target = $profile->value;
        $profiles = collect($user->music_profiles ?? []);

        if ($enabled) {
            if (! $profiles->contains(static fn (string $value): bool => $value === $target)) {
                $profiles->push($target);
            }
        } else {
            $profiles = $profiles->reject(static fn (string $value): bool => $value === $target)->values();
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();

        if ($enabled) {
            if ($profile === UserMusicProfile::Musician && $user->musician === null && Gate::allows('create', Musician::class)) {
                Musician::create([
                    'user_id' => $user->id,
                    'name' => (string) $user->name,
                    'is_session' => $user->hasMusicProfile(UserMusicProfile::SessionMusician),
                ]);
            }
            if ($profile === UserMusicProfile::Teacher && $user->teacher === null && Gate::allows('create', Teacher::class)) {
                Teacher::create([
                    'user_id' => $user->id,
                    'name' => (string) $user->name,
                ]);
            }
        }

        if ($profile === UserMusicProfile::SessionMusician) {
            $user->load('musician');
            $musician = $user->musician;
            if ($musician !== null) {
                $musician->is_session = $user->hasMusicProfile(UserMusicProfile::SessionMusician);
                $musician->save();
            }
        }

        $this->dispatch('music-profiles-updated');
    }

    public function addLayoutBlock(string $key): void
    {
        if (! isset($this->rows[$key])) {
            return;
        }

        $type = (string) ($this->rows[$key]['type'] ?? '');
        $blockId = trim((string) ($this->selectedLayoutBlockId[$key] ?? ''));
        if ($blockId === '') {
            return;
        }

        $catalog = $this->blockCatalogByType($type);
        $knownBlockIds = array_map(static fn (array $row): string => (string) $row['id'], $catalog);
        if (! in_array($blockId, $knownBlockIds, true)) {
            return;
        }

        $this->layoutBlockEnabled[$key] ??= [];
        $this->layoutBlockEnabled[$key][$blockId] = true;
        $this->selectedLayoutBlockId[$key] = '';
    }

    public function removeLayoutBlock(string $key, string $blockId): void
    {
        if (! isset($this->rows[$key])) {
            return;
        }

        $this->layoutBlockEnabled[$key] ??= [];
        $this->layoutBlockEnabled[$key][$blockId] = false;
    }

    public function render(): View
    {
        return view('livewire.music.public-page-settings-modal');
    }

    /**
     * @return list<array{
     *   key: string,
     *   id: int,
     *   type: string,
     *   label: string,
     *   name: string,
     *   slug: string,
     *   enabled: bool,
     *   layout_draft: mixed,
     *   profile_enabled: bool
     * }>
     */
    public function sortedRows(): array
    {
        $rows = array_values($this->rows);
        $position = [];
        foreach (array_values($rows) as $idx => $row) {
            $position[(string) ($row['key'] ?? '')] = $idx;
        }

        usort(
            $rows,
            function (array $a, array $b) use ($position): int {
                $keyA = (string) ($a['key'] ?? '');
                $keyB = (string) ($b['key'] ?? '');
                $onA = str_starts_with($keyA, 'user_profile:')
                    ? $this->normalizeBool($a['profile_enabled'] ?? false)
                    : $this->normalizeBool($a['enabled'] ?? false);
                $onB = str_starts_with($keyB, 'user_profile:')
                    ? $this->normalizeBool($b['profile_enabled'] ?? false)
                    : $this->normalizeBool($b['enabled'] ?? false);

                if ($onA !== $onB) {
                    return ($onB ? 1 : 0) <=> ($onA ? 1 : 0);
                }

                $prio = $this->rowStablePriority($a) <=> $this->rowStablePriority($b);
                if ($prio !== 0) {
                    return $prio;
                }

                return ($position[$keyA] ?? 0) <=> ($position[$keyB] ?? 0);
            }
        );

        return $rows;
    }

    private function reloadRows(): void
    {
        $userId = (int) Auth::id();
        $rows = [];

        if ($this->panel === 'user_profiles') {
            foreach ($this->userProfileRows() as $profileRow) {
                $rows[] = $profileRow;
            }
        } else {
            $musician = Musician::query()->where('user_id', $userId)->first();
            if ($musician) {
                $rows[] = $this->buildRow('musician', $musician, __('ui.public_profile.type_musician'));
            }

            $teacher = Teacher::query()->where('user_id', $userId)->first();
            if ($teacher) {
                $rows[] = $this->buildRow('teacher', $teacher, __('ui.public_profile.type_teacher'));
            }

            foreach (Peformer::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('performer', $model, __('ui.public_profile.type_performer'));
            }
            foreach (Studio::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('studio', $model, __('ui.public_profile.type_studio'));
            }
            foreach (Rehersal::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('rehearsal', $model, __('ui.public_profile.type_rehearsal'));
            }
            foreach (ConcertVenue::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('concert_venue', $model, __('ui.public_profile.type_concert_venue'));
            }
            foreach (School::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('school', $model, __('ui.public_profile.type_school'));
            }
            foreach (RecordLabel::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('record_label', $model, __('ui.public_profile.type_record_label'));
            }
            foreach (ProducerCenter::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('producer_center', $model, __('ui.public_profile.type_producer_center'));
            }
            foreach (Shop::query()->where('owner_user_id', $userId)->orderBy('name')->get() as $model) {
                $rows[] = $this->buildRow('shop', $model, __('ui.public_profile.type_shop'));
            }
        }

        $this->rows = [];
        $this->slugs = [];
        $this->enabled = [];
        $this->layoutBlockEnabled = [];
        $this->selectedLayoutBlockId = [];
        $this->profileEnabled = [];

        foreach ($rows as $row) {
            $key = $row['key'];
            $this->rows[$key] = $row;
            $this->slugs[$key] = $row['slug'];
            $this->enabled[$key] = $this->normalizeBool($row['enabled'] ?? false);
            $this->layoutBlockEnabled[$key] = $this->resolveLayoutStateForModelType(
                $row['type'],
                $row['layout_draft'] ?? null
            );
            $this->selectedLayoutBlockId[$key] = '';
            $this->profileEnabled[$key] = $this->normalizeBool($row['profile_enabled'] ?? false);
        }
    }

    private function normalizeBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return match ($normalized) {
                '', '0', 'false', 'no', 'off', 'n' => false,
                '1', 'true', 'yes', 'on', 'y' => true,
                default => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            };
        }

        return (bool) $value;
    }

    private function userProfileRowOrderIndex(string $key): int
    {
        $profile = $this->extractUserProfileFromKey($key);
        if ($profile === null) {
            return 9999;
        }

        foreach ($this->managedUserProfiles() as $index => $candidate) {
            if ($candidate === $profile) {
                return $index;
            }
        }

        return 9998;
    }

    /**
     * Lower value sorts earlier among rows with the same enabled flag.
     */
    private function rowStablePriority(array $row): int
    {
        $key = (string) ($row['key'] ?? '');
        if (str_starts_with($key, 'user_profile:')) {
            return $this->userProfileRowOrderIndex($key);
        }

        $type = (string) ($row['type'] ?? '');

        return match ($type) {
            'musician' => 200,
            'teacher' => 201,
            default => 300,
        };
    }

    /**
     * @return array{
     *   key: string,
     *   id: int,
     *   type: string,
     *   label: string,
     *   name: string,
     *   slug: string,
     *   enabled: bool,
     *   layout_draft: mixed,
     *   profile_enabled: bool
     * }
     */
    private function buildRow(string $type, Model $model, string $label): array
    {
        return [
            'key' => $type.':'.$model->getKey(),
            'id' => (int) $model->getKey(),
            'type' => $type,
            'label' => $label,
            'name' => (string) ($model->name ?? '#'.$model->getKey()),
            'slug' => (string) ($model->slug ?? ''),
            'enabled' => (bool) ($model->public_page_enabled ?? false),
            'layout_draft' => $model->layout_draft,
            'profile_enabled' => false,
        ];
    }

    private function resolveOwnedModelByKey(string $key): ?Model
    {
        $type = $this->extractTypeFromKey($key);
        $id = $this->extractIdFromKey($key);
        $userId = (int) Auth::id();

        if ($type === null || $id === null) {
            return null;
        }

        return match ($type) {
            'musician' => Musician::query()->whereKey($id)->where('user_id', $userId)->first(),
            'teacher' => Teacher::query()->whereKey($id)->where('user_id', $userId)->first(),
            'performer' => Peformer::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'studio' => Studio::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'rehearsal' => Rehersal::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'concert_venue' => ConcertVenue::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'school' => School::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'record_label' => RecordLabel::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'producer_center' => ProducerCenter::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            'shop' => Shop::query()->whereKey($id)->where('owner_user_id', $userId)->first(),
            default => null,
        };
    }

    private function extractTypeFromKey(string $key): ?string
    {
        $parts = explode(':', $key, 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            return null;
        }

        return $parts[0];
    }

    private function extractIdFromKey(string $key): ?int
    {
        $parts = explode(':', $key, 2);
        if (count($parts) !== 2 || ! ctype_digit($parts[1])) {
            return null;
        }

        return (int) $parts[1];
    }

    private function slugTableByType(string $type): string
    {
        return match ($type) {
            'musician' => 'musicians',
            'teacher' => 'teachers',
            'performer' => 'peformers',
            'studio' => 'studios',
            'rehearsal' => 'rehearsals',
            'concert_venue' => 'concert_venues',
            'school' => 'schools',
            'record_label' => 'record_labels',
            'producer_center' => 'producer_centers',
            'shop' => 'shops',
            default => 'musicians',
        };
    }

    /**
     * @return list<array{id: string, label_key: string}>
     */
    private function blockCatalogByType(string $type): array
    {
        return match ($type) {
            'musician' => PublicProfileBlocks::musicianCatalog(),
            'teacher' => PublicProfileBlocks::teacherCatalog(),
            'performer' => PublicProfileBlocks::performerCatalog(),
            'studio', 'rehearsal', 'concert_venue', 'school', 'record_label', 'producer_center' => PublicProfileBlocks::venueCatalog(),
            'shop' => PublicProfileBlocks::shopCatalog(),
            default => PublicProfileBlocks::musicianCatalog(),
        };
    }

    /**
     * @return array<string, bool>
     */
    private function resolveLayoutStateForModelType(string $type, mixed $layoutDraft): array
    {
        $catalog = $this->blockCatalogByType($type);
        $out = [];

        foreach ($catalog as $row) {
            $out[$row['id']] = true;
        }

        if (! is_array($layoutDraft) || ! isset($layoutDraft['blocks']) || ! is_array($layoutDraft['blocks'])) {
            return $out;
        }

        foreach ($layoutDraft['blocks'] as $block) {
            if (! is_array($block) || ! isset($block['id'])) {
                continue;
            }

            $id = (string) $block['id'];
            if (array_key_exists($id, $out)) {
                $out[$id] = (bool) ($block['enabled'] ?? true);
            }
        }

        return $out;
    }

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    private function buildLayoutDraftBlocks(string $type, string $key): array
    {
        $catalog = $this->blockCatalogByType($type);
        $state = $this->layoutBlockEnabled[$key] ?? [];
        $blocks = [];

        foreach (array_values($catalog) as $order => $row) {
            $id = $row['id'];
            $blocks[] = [
                'id' => $id,
                'enabled' => (bool) ($state[$id] ?? true),
                'order' => $order,
            ];
        }

        return $blocks;
    }

    /**
     * @return list<array{
     *   key: string,
     *   id: int,
     *   type: string,
     *   label: string,
     *   name: string,
     *   slug: string,
     *   enabled: bool,
     *   layout_draft: mixed,
     *   profile_enabled: bool
     * }>
     */
    private function userProfileRows(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $rows = [];

        foreach ($this->managedUserProfiles() as $profile) {
            $rows[] = [
                'key' => 'user_profile:'.$profile->value,
                'id' => (int) $user->id,
                'type' => 'user',
                'label' => __('ui.music.profile_tab_'.$this->tabByProfile($profile)),
                'name' => (string) $user->name,
                'slug' => '',
                'enabled' => false,
                'layout_draft' => null,
                'profile_enabled' => $user->hasMusicProfile($profile),
            ];
        }

        return $rows;
    }

    /**
     * @return list<UserMusicProfile>
     */
    private function managedUserProfiles(): array
    {
        return [
            UserMusicProfile::Musician,
            UserMusicProfile::Teacher,
            UserMusicProfile::EventOrganizer,
            UserMusicProfile::Manager,
            UserMusicProfile::SessionMusician,
            UserMusicProfile::VenueRepresentative,
            UserMusicProfile::Agent,
            UserMusicProfile::SoundEngineer,
            UserMusicProfile::Arranger,
            UserMusicProfile::LiveSound,
            UserMusicProfile::LightingDesigner,
            UserMusicProfile::Videographer,
            UserMusicProfile::Photographer,
            UserMusicProfile::Journalist,
            UserMusicProfile::VenueManager,
            UserMusicProfile::Merchandiser,
            UserMusicProfile::TourManager,
            UserMusicProfile::Promoter,
            UserMusicProfile::RecordingEngineer,
            UserMusicProfile::MasteringEngineer,
            UserMusicProfile::SessionProducer,
            UserMusicProfile::TechRider,
            UserMusicProfile::BacklineTech,
            UserMusicProfile::GraphicDesigner,
            UserMusicProfile::SmmManager,
            UserMusicProfile::MusicLawyer,
            UserMusicProfile::Accountant,
        ];
    }

    private function extractUserProfileFromKey(string $key): ?UserMusicProfile
    {
        if (! str_starts_with($key, 'user_profile:')) {
            return null;
        }

        $value = substr($key, strlen('user_profile:'));

        return UserMusicProfile::tryFrom($value) ?: null;
    }

    private function tabByProfile(UserMusicProfile $profile): string
    {
        return match ($profile) {
            UserMusicProfile::EventOrganizer => 'organizer',
            default => $profile->value,
        };
    }
}
