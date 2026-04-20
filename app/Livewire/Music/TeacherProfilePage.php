<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\LegalEntityType;
use App\Enums\UserMusicProfile;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Music\PublicProfileBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TeacherProfilePage extends Component
{
    public bool $enabled = false;

    public ?Teacher $record = null;

    public string $name = '';

    public string $description = '';

    public string $slug = '';

    public bool $public_page_enabled = false;

    public bool $available_other_cities = false;

    public ?string $legal_entity_type = null;

    public string $company_name = '';

    public string $inn = '';

    public string $ogrn = '';

    /** @var array<string, bool> */
    public array $layoutBlockEnabled = [];

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->enabled = $user->canActAsTeacher();
        $this->record = $user->teacher;
        if ($this->record === null) {
            Gate::authorize('create', Teacher::class);
            $this->record = Teacher::create([
                'user_id' => $user->id,
                'name' => (string) $user->name,
            ]);
        }

        $catalog = PublicProfileBlocks::teacherCatalog();
        foreach ($catalog as $row) {
            $this->layoutBlockEnabled[$row['id']] = true;
        }

        if ($this->record) {
            Gate::authorize('update', $this->record);
            $this->name = (string) $this->record->name;
            $this->description = (string) ($this->record->description ?? '');
            $this->slug = (string) ($this->record->slug ?? '');
            $this->public_page_enabled = (bool) $this->record->public_page_enabled;
            $this->available_other_cities = (bool) $this->record->available_other_cities;
            $this->legal_entity_type = $this->record->legal_entity_type?->value;
            $this->company_name = (string) ($this->record->company_name ?? '');
            $this->inn = (string) ($this->record->inn ?? '');
            $this->ogrn = (string) ($this->record->ogrn ?? '');

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
            Gate::authorize('create', Teacher::class);
        }
        $this->name = (string) $user->name;
    }

    public function toggleProfile(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $profiles = collect($user->music_profiles ?? []);
        $target = UserMusicProfile::Teacher->value;

        if ($profiles->contains($target)) {
            $profiles = $profiles->reject(fn (string $value) => $value === $target)->values();
        } else {
            $profiles->push($target);
        }

        $user->music_profiles = $profiles->unique()->values()->all();
        $user->save();
        $this->enabled = $user->canActAsTeacher();
        $this->dispatch('music-profiles-updated');

        if ($this->enabled && $this->record === null) {
            $this->record = Teacher::create([
                'user_id' => $user->id,
                'name' => (string) $user->name,
            ]);
        }

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
            Gate::authorize('create', Teacher::class);
        }

        $slugRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('teachers', 'slug')->ignore($this->record?->id),
        ];
        if ($this->public_page_enabled) {
            $slugRules = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('teachers', 'slug')->ignore($this->record?->id),
            ];
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'slug' => $slugRules,
            'public_page_enabled' => ['boolean'],
            'available_other_cities' => ['boolean'],
            'legal_entity_type' => ['nullable', 'string', Rule::in(array_map(fn (LegalEntityType $c) => $c->value, LegalEntityType::cases()))],
            'company_name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:32'],
            'ogrn' => ['nullable', 'string', 'max:32'],
        ], [
            'slug.required' => __('ui.music.validation.slug_required_public'),
            'slug.regex' => __('ui.music.validation.slug_format'),
        ]);

        $layoutDraft = PublicProfileBlocks::wrapVersion1($this->buildLayoutBlocks());

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => $validated['slug'] ?: null,
            'public_page_enabled' => $validated['public_page_enabled'],
            'available_other_cities' => $validated['available_other_cities'],
            'legal_entity_type' => $validated['legal_entity_type'] ?: null,
            'company_name' => $validated['company_name'] ?: null,
            'inn' => $validated['inn'] ?: null,
            'ogrn' => $validated['ogrn'] ?: null,
            'layout_draft' => $layoutDraft,
        ];

        if ($this->record === null) {
            $payload['user_id'] = Auth::id();
            $this->record = Teacher::create($payload);
        } else {
            $this->record->update($payload);
        }

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

    /**
     * @return list<array{id: string, enabled: bool, order: int}>
     */
    private function buildLayoutBlocks(): array
    {
        $catalog = PublicProfileBlocks::teacherCatalog();
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
        return view('livewire.music.teacher-profile-page', [
            'blockCatalog' => PublicProfileBlocks::teacherCatalog(),
        ]);
    }
}
