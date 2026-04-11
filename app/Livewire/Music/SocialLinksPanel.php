<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Social;
use App\Models\Studio;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SocialLinksPanel extends Component
{
    public string $ownerKind = 'musician';

    public int $ownerId = 0;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?string $notice = null;

    public string $form_link = '';

    public ?string $form_type = null;

    public string $form_name = '';

    public string $form_description = '';

    public bool $form_active = true;

    public string $filterType = '';

    public string $filterState = 'all';

    public string $filterQuery = '';

    public function mount(string $ownerKind, int $ownerId): void
    {
        if (! in_array($ownerKind, $this->allowedKinds(), true)) {
            abort(404);
        }

        $this->ownerKind = $ownerKind;
        $this->ownerId = $ownerId;
        $this->authorizeOwner($this->resolveOwner());
    }

    public function openCreate(): void
    {
        $this->authorizeOwner($this->resolveOwner());
        $this->editingId = null;
        $this->showForm = true;
        $this->notice = null;
        $this->form_link = '';
        $this->form_type = null;
        $this->form_name = '';
        $this->form_description = '';
        $this->form_active = true;
    }

    public function openEdit(int $socialId): void
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);
        $social = $this->findOwnedSocial($socialId);

        $this->editingId = $social->id;
        $this->showForm = true;
        $this->notice = null;
        $this->form_link = (string) $social->link;
        $this->form_type = $social->type ?: null;
        $this->form_name = (string) ($social->name ?? '');
        $this->form_description = (string) ($social->description ?? '');
        $this->form_active = (bool) $social->active;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);

        $validated = $this->validate([
            'form_link' => ['required', 'url', 'max:2048'],
            'form_type' => ['nullable', 'string', Rule::in($this->socialTypes())],
            'form_name' => ['nullable', 'string', 'max:255'],
            'form_description' => ['nullable', 'string', 'max:2000'],
            'form_active' => ['boolean'],
        ]);

        $payload = [
            'link' => $validated['form_link'],
            'type' => $validated['form_type'] ?: null,
            'name' => $validated['form_name'] ?: null,
            'description' => $validated['form_description'] ?: null,
            'active' => $validated['form_active'],
        ];

        if ($this->editingId === null) {
            $payload['socialable_id'] = $owner->getKey();
            $payload['socialable_type'] = $owner->getMorphClass();
            $payload['sort_order'] = (int) (Social::query()
                ->where('socialable_id', $owner->getKey())
                ->where('socialable_type', $owner->getMorphClass())
                ->max('sort_order') ?? 0) + 10;

            Social::query()->create($payload);
            $this->notice = __('ui.social.saved');
        } else {
            $this->findOwnedSocial($this->editingId)->update($payload);
            $this->notice = __('ui.social.updated');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function deleteSocial(int $socialId): void
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);
        $this->findOwnedSocial($socialId)->delete();
        $this->notice = __('ui.social.deleted');
    }

    public function toggleActive(int $socialId): void
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);
        $social = $this->findOwnedSocial($socialId);
        $social->active = ! (bool) $social->active;
        $social->save();

        $this->notice = $social->active
            ? __('ui.social.activated')
            : __('ui.social.deactivated');
    }

    public function resetFilters(): void
    {
        $this->filterType = '';
        $this->filterState = 'all';
        $this->filterQuery = '';
    }

    public function render(): View
    {
        $owner = $this->resolveOwner();
        $this->authorizeOwner($owner);

        $query = Social::query()
            ->where('socialable_id', $owner->getKey())
            ->where('socialable_type', $owner->getMorphClass());

        if ($this->filterType !== '' && in_array($this->filterType, $this->socialTypes(), true)) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterState === 'active') {
            $query->where('active', true);
        } elseif ($this->filterState === 'inactive') {
            $query->where('active', false);
        }

        $needle = trim($this->filterQuery);
        if ($needle !== '') {
            $query->where(function ($inner) use ($needle): void {
                $inner->where('name', 'like', "%{$needle}%")
                    ->orWhere('link', 'like', "%{$needle}%")
                    ->orWhere('description', 'like', "%{$needle}%");
            });
        }

        $links = $query
            ->orderByDesc('active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('livewire.music.social-links-panel', [
            'links' => $links,
            'types' => $this->socialTypes(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function socialTypes(): array
    {
        return [
            'youtube',
            'instagram',
            'facebook',
            'vk',
            'telegram',
            'twitter',
            'tiktok',
            'portfolio',
            'website',
            'soundcloud',
            'spotify',
            'apple_music',
            'other',
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedKinds(): array
    {
        return ['user', 'musician', 'teacher', 'performer', 'studio', 'rehearsal', 'concert_venue', 'school', 'record_label', 'producer_center', 'shop'];
    }

    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(): string
    {
        return match ($this->ownerKind) {
            'user' => User::class,
            'musician' => Musician::class,
            'teacher' => Teacher::class,
            'performer' => Peformer::class,
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'concert_venue' => ConcertVenue::class,
            'school' => School::class,
            'record_label' => RecordLabel::class,
            'producer_center' => ProducerCenter::class,
            'shop' => Shop::class,
            default => abort(404),
        };
    }

    private function resolveOwner(): Model
    {
        $class = $this->resolveModelClass();

        return $class::query()->findOrFail($this->ownerId);
    }

    private function authorizeOwner(Model $owner): void
    {
        if ($owner instanceof User) {
            abort_unless((int) $owner->id === (int) Auth::id(), 403);

            return;
        }

        Gate::authorize('update', $owner);
    }

    private function findOwnedSocial(int $id): Social
    {
        $owner = $this->resolveOwner();

        return Social::query()
            ->where('socialable_id', $owner->getKey())
            ->where('socialable_type', $owner->getMorphClass())
            ->whereKey($id)
            ->firstOrFail();
    }
}
