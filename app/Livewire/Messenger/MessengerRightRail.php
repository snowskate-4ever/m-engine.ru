<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Узкая правая колонка с быстрым доступом к чатам (как app-internal-chat-rail в CRM).
 */
class MessengerRightRail extends Component
{
    /** @var list<array{id:int, name:string, initials:string, unread_count:int, type:string}> */
    public array $chats = [];

    public function mount(MessengerService $messenger): void
    {
        $this->refreshList($messenger);
    }

    #[On('messenger-conversations-refresh')]
    public function refreshList(?MessengerService $messenger = null): void
    {
        $messenger ??= app(MessengerService::class);
        $user = Auth::user();
        if ($user === null) {
            $this->chats = [];

            return;
        }

        $rows = $messenger->listConversationsSummary($user);
        $this->chats = collect($rows)
            ->take(20)
            ->map(function (array $row): array {
                $name = (string) ($row['title'] ?? '');
                if (($row['type'] ?? '') === 'direct' && ! empty($row['direct_peer']['name'])) {
                    $name = (string) $row['direct_peer']['name'];
                }
                if ($name === '') {
                    $name = __('ui.messenger.chat');
                }

                $initials = Str::upper(Str::of($name)->explode(' ')->filter()->take(2)->map(
                    fn ($w) => Str::substr((string) $w, 0, 1)
                )->implode(''));
                if ($initials === '') {
                    $initials = Str::upper(Str::substr($name, 0, 2));
                }

                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'name' => $name,
                    'initials' => $initials,
                    'unread_count' => (int) ($row['unread_count'] ?? 0),
                    'type' => (string) ($row['type'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.messenger.messenger-right-rail');
    }
}
