<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\PerformerMembershipStatus;
use App\Models\Musician;
use App\Models\Peformer;
use App\Services\Music\PerformerMembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PerformerLineupPanel extends Component
{
    public int $peformerId;

    public ?int $inviteMusicianId = null;

    public ?string $notice = null;

    public function mount(int $peformerId): void
    {
        $this->peformerId = $peformerId;
        $peformer = Peformer::findOrFail($peformerId);
        Gate::authorize('manageMembers', $peformer);
    }

    public function invite(): void
    {
        $peformer = $this->loadPeformer();
        Gate::authorize('manageMembers', $peformer);

        $validated = $this->validate([
            'inviteMusicianId' => ['required', 'integer', 'exists:musicians,id'],
        ], [], [
            'inviteMusicianId' => __('ui.music.lineup_musician_field'),
        ]);

        $musician = Musician::findOrFail($validated['inviteMusicianId']);
        try {
            app(PerformerMembershipService::class)->invite($peformer, $musician, Auth::user());
        } catch (ValidationException $e) {
            $messages = $e->errors();
            if (isset($messages['musician_id'])) {
                throw ValidationException::withMessages(['inviteMusicianId' => $messages['musician_id']]);
            }

            throw $e;
        }

        $this->reset('inviteMusicianId');
        $this->notice = __('ui.music.lineup_invited');
    }

    public function cancelInvite(int $musicianId): void
    {
        $peformer = $this->loadPeformer();
        Gate::authorize('manageMembers', $peformer);
        $musician = Musician::findOrFail($musicianId);
        app(PerformerMembershipService::class)->cancelPending($peformer, $musician, Auth::user());
        $this->notice = __('ui.music.lineup_invite_cancelled');
    }

    public function removeMember(int $musicianId): void
    {
        $peformer = $this->loadPeformer();
        Gate::authorize('manageMembers', $peformer);
        $musician = Musician::findOrFail($musicianId);
        app(PerformerMembershipService::class)->setAcceptedLeft($peformer, $musician, Auth::user());
        $this->notice = __('ui.music.lineup_member_removed');
    }

    public function render(): View
    {
        $peformer = $this->loadPeformer();

        $busyIds = DB::table('peformer_musician')
            ->where('peformer_id', $peformer->id)
            ->whereIn('status', [
                PerformerMembershipStatus::Accepted->value,
                PerformerMembershipStatus::Pending->value,
            ])
            ->pluck('musician_id');

        $inviteOptions = Musician::query()
            ->whereNotNull('user_id')
            ->whereNotIn('id', $busyIds)
            ->orderBy('name')
            ->limit(400)
            ->get(['id', 'name']);

        return view('livewire.music.performer-lineup-panel', [
            'peformer' => $peformer,
            'inviteOptions' => $inviteOptions,
        ]);
    }

    private function loadPeformer(): Peformer
    {
        $peformer = Peformer::query()
            ->with(['musicians' => fn ($q) => $q->orderBy('name')])
            ->findOrFail($this->peformerId);
        Gate::authorize('manageMembers', $peformer);

        return $peformer;
    }
}
