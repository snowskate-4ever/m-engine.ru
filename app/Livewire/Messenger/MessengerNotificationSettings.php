<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MessengerNotificationSettings extends Component
{
    public bool $pushEnabled = true;

    public function mount(MessengerService $messenger): void
    {
        $this->pushEnabled = $messenger->preferencesToArray(Auth::user())['push_enabled'];
    }

    public function updatedPushEnabled(mixed $value): void
    {
        app(MessengerService::class)->updatePreferences(Auth::user(), (bool) $value);
    }

    public function render()
    {
        return view('livewire.messenger.messenger-notification-settings')
            ->layout('components.layouts.second_level_layout', [
                'title' => __('ui.messenger.notifications_title'),
            ]);
    }
}
