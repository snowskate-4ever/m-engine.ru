<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\PerformerMembershipStatus;
use App\Enums\UserMusicProfile;
use App\Models\Instrument;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\User;
use App\Services\Music\PerformerMembershipService;
use App\Support\Music\PublicProfileBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MusicianProfilePage extends Component
{
    public bool $enabled = false;

    public ?Musician $record = null;

    public string $name = '';

    public string $description = '';

    public string $bio = '';

    public string $slug = '';

    public bool $public_page_enabled = false;

    /** @var list<int> */
    public array $instrumentIds = [];

    /** @var array<string, bool> */
    public array $layoutBlockEnabled = [];

    public ?string $lineupNotice = null;

    public ?string $lineupError = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->enabled = $user->canActAsMusician();
        $this->record = $user->musician;
        if ($this->record === null) {
            Gate::authorize('create', Musician::class);
            $this->record = Musician::create([
                'user_id' => $user->id,
                'name' => (string) $user->name,
                'is_session' => $user->canActAsSessionMusician(),
            ]);
        }

        $catalog = PublicProfileBlocks::musicianCatalog();
        foreach ($catalog as $row) {
            $this->layoutBlockEnabled[$row['id']] = true;
        }

        if ($this->record) {
            Gate::authorize('update', $this->record);
            $this->name = (string) $this->record->name;
            $this->description = (string) ($this->record->description ?? '');
            $this->bio = (string) ($this->record->bio ?? '');
            $this->slug = (string) ($this->record->slug ?? '');
            $this->public_page_enabled = (bool) $this->record->public_page_enabled;
            $this->instrumentIds = $this->record->instruments->pluck('id')->all();

            $draft = $this->record->layout_draft;
            if (is_array($draft) && ! empty($draft['blocks']) && is_array($draft['blocks'])) {
                foreach ($draft['blocks'] as $b) {
                    if (is_array($b) && isset($b['id'])) {
                        $this->layoutBlockEnabled[(string) $b['id']] = (bool) ($b['enabled'] ?? true);
                    }
                }
            }

            return;
        }

        if ($this->enabled) {
            Gate::authorize('create', Musician::class);
        }
        $this->name = (string) $user->name;
    }

    public function toggleProfile(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::Musician->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();
        $this->enabled = $user->canActAsMusician();
        session()->flash('success', __('ui.music.saved'));
    }

    public function save(): void
    {
        if (! $this->enabled) {
            $this->addError('name', __('ui.music.profile_enable_required'));

            return;
        }

        if ($this->record) {
            Gate::authorize('update', $this->record);
        } else {
            Gate::authorize('create', Musician::class);
        }

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('musicians', 'slug')->ignore($this->record?->id),
        ];
        if ($this->public_page_enabled) {
            $slugRules = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('musicians', 'slug')->ignore($this->record?->id),
            ];
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'slug' => $slugRules,
            'public_page_enabled' => ['boolean'],
            'instrumentIds' => ['required', 'array', 'min:1'],
            'instrumentIds.*' => ['integer', 'exists:instruments,id'],
        ], [
            'instrumentIds.required' => __('ui.music.validation.instruments_required'),
            'instrumentIds.min' => __('ui.music.validation.instruments_required'),
            'slug.required' => __('ui.music.validation.slug_required_public'),
            'slug.regex' => __('ui.music.validation.slug_format'),
        ]);

        $layoutDraft = PublicProfileBlocks::wrapVersion1($this->buildLayoutBlocks());
        /** @var User $user */
        $user = Auth::user();

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'slug' => $validated['slug'] ?: null,
            'public_page_enabled' => $validated['public_page_enabled'],
            'is_session' => $user->canActAsSessionMusician(),
            'layout_draft' => $layoutDraft,
        ];

        if ($this->record === null) {
            $payload['user_id'] = Auth::id();
            $this->record = Musician::create($payload);
        } else {
            $this->record->update($payload);
        }

        $this->record->instruments()->sync($validated['instrumentIds']);

        session()->flash('success', __('ui.music.saved'));
    }

    public function publishLayout(): void
    {
        $this->save();
        if ($this->record === null) {
            return;
        }
        Gate::authorize('update', $this->record);
        $draft = PublicProfileBlocks::wrapVersion1($this->buildLayoutBlocks());
        $this->record->layout_draft = $draft;
        $this->record->layout_published = $draft;
        $this->record->save();

        session()->flash('success', __('ui.music.layout_published'));
    }

    public function acceptLineupInvite(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        try {
            app(PerformerMembershipService::class)->accept(
                Peformer::findOrFail($peformerId),
                $this->record,
                Auth::user(),
            );
        } catch (ValidationException $e) {
            $this->lineupError = $e->errors()['lineup'][0] ?? null;

            return;
        }
        $this->lineupNotice = __('ui.music.lineup_accepted');
    }

    public function declineLineupInvite(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        app(PerformerMembershipService::class)->decline(
            Peformer::findOrFail($peformerId),
            $this->record,
            Auth::user(),
        );
        $this->lineupNotice = __('ui.music.lineup_declined');
    }

    public function leaveLineup(int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        app(PerformerMembershipService::class)->leave(
            Peformer::findOrFail($peformerId),
            $this->record,
            Auth::user(),
        );
        $this->lineupNotice = __('ui.music.lineup_left');
    }

    public function setLineupShowOnProfile(bool $show, int $peformerId): void
    {
        $this->requireMusician();
        Gate::authorize('update', $this->record);
        $this->lineupError = null;
        try {
            app(PerformerMembershipService::class)->setShowOnMusicianProfile(
                Peformer::findOrFail($peformerId),
                $this->record,
                Auth::user(),
                $show,
            );
        } catch (ValidationException $e) {
            $this->lineupError = $e->errors()['lineup'][0] ?? null;

            return;
        }
        $this->lineupNotice = __('ui.music.lineup_visibility_updated');
    }

    private function requireMusician(): void
    {
        $this->record ??= Auth::user()->musician;
        if ($this->record === null) {
            abort(403);
        }
    }

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    private function buildLayoutBlocks(): array
    {
        $catalog = PublicProfileBlocks::musicianCatalog();
        $out = [];
        foreach (array_values($catalog) as $order => $row) {
            $id = $row['id'];
            $out[] = [
                'id' => $id,
                'enabled' => (bool) ($this->layoutBlockEnabled[$id] ?? false),
                'order' => $order,
            ];
        }

        return $out;
    }

    public function render(): View
    {
        $instruments = Instrument::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.music.musician-profile-page', [
            'instruments' => $instruments,
            'blockCatalog' => PublicProfileBlocks::musicianCatalog(),
            'pendingLineupInvites' => $this->record
                ? $this->record->peformers()->wherePivot('status', PerformerMembershipStatus::Pending->value)->orderBy('name')->get()
                : collect(),
            'acceptedLineup' => $this->record
                ? $this->record->peformers()->wherePivot('status', PerformerMembershipStatus::Accepted->value)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
