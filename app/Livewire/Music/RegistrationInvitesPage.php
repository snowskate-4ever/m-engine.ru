<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Models\RegistrationInvite;
use App\Services\Auth\RegistrationInviteService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RegistrationInvitesPage extends Component
{
    public function createInvite(): void
    {
        app(RegistrationInviteService::class)->createForUser(Auth::user());

        session()->flash('success', __('ui.auth.registration_invites.created'));
    }

    public function render(): View
    {
        return view('livewire.music.registration-invites-page', [
            'invites' => $this->inviteRows(),
        ]);
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     created_at: string,
     *     used_at: ?string,
     *     is_active: bool,
     *     status_label: string,
     *     registration_url: ?string
     * }>
     */
    private function inviteRows(): Collection
    {
        $inviteService = app(RegistrationInviteService::class);

        return RegistrationInvite::query()
            ->where('created_by_user_id', Auth::id())
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(function (RegistrationInvite $invite) use ($inviteService): array {
                $isActive = $invite->is_active && $invite->used_at === null;

                return [
                    'id' => (int) $invite->id,
                    'created_at' => optional($invite->created_at)->format('Y-m-d H:i') ?? '-',
                    'used_at' => optional($invite->used_at)->format('Y-m-d H:i'),
                    'is_active' => $isActive,
                    'status_label' => $isActive
                        ? __('ui.auth.registration_invites.status_active')
                        : __('ui.auth.registration_invites.status_used'),
                    'registration_url' => $isActive ? $inviteService->registrationUrlForInvite($invite) : null,
                ];
            });
    }
}
