<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MessengerNotificationSettings extends Component
{
    public bool $inAppEnabled = true;

    public bool $pushEnabled = true;

    public bool $musicLineupEmail = true;

    public function mount(MessengerService $messenger): void
    {
        $user = Auth::user();
        $prefs = $messenger->preferencesToArray($user);
        $this->inAppEnabled = $prefs['in_app_enabled'];
        $this->pushEnabled = $prefs['push_enabled'];
        $this->musicLineupEmail = $prefs['music_lineup_email'];
    }

    public function updatedInAppEnabled(mixed $value): void
    {
        Auth::user()->setInAppNotifications((bool) $value);
    }

    public function updatedPushEnabled(mixed $value): void
    {
        app(MessengerService::class)->updatePreferences(Auth::user(), (bool) $value);
    }

    public function updatedMusicLineupEmail(mixed $value): void
    {
        Auth::user()->setMusicLineupInvitationEmail((bool) $value);
    }

    public function render()
    {
        return view('livewire.messenger.messenger-notification-settings')
            ->layout('components.layouts.second_level_layout', [
                'title' => __('ui.messenger.notifications_title'),
            ]);
    }
}
