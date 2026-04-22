<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Services\Messenger\MessengerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Узкая правая колонка с быстрым доступом к чатам (как app-internal-chat-rail в CRM).
 */
class MessengerRightRail extends Component
{
    /** @var list<array{id:int, name:string, initials:string, unread_count:int, type:string, is_online:bool|null}> */
    public array $chats = [];

    public function mount(MessengerService $messenger): void
    {
        $this->refreshList($messenger);
    }

    public function selectConversation(int $conversationId): void
    {
        foreach ($this->chats as $index => $chat) {
            if ((int) ($chat['id'] ?? 0) === $conversationId) {
                $this->chats[$index]['unread_count'] = 0;
                break;
            }
        }

        if (request()->routeIs(['messenger.index', 'messenger.show'])) {
            $this->redirectRoute('messenger.show', ['conversation' => $conversationId], navigate: true);

            return;
        }

        $this->dispatch('messenger-float-open-chat', id: $conversationId);
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
                    'is_support_chat' => (bool) ($row['is_support_chat'] ?? false),
                    'is_online' => $this->directPeerOnline($row),
                ];
            })
            ->values()
            ->all();

        $chatIds = array_values(array_filter(array_map(
            static fn (array $chat): int => (int) ($chat['id'] ?? 0),
            $this->chats
        )));
        if ($chatIds !== []) {
            $this->dispatch('messenger-chats-loaded', chatIds: $chatIds);
        }
    }

    #[On('messenger-chat-opened')]
    public function onChatOpened(int $conversationId): void
    {
        foreach ($this->chats as $index => $chat) {
            if ((int) ($chat['id'] ?? 0) === $conversationId) {
                $this->chats[$index]['unread_count'] = 0;
                break;
            }
        }
    }

    private function directPeerOnline(array $row): ?bool
    {
        if (array_key_exists('is_online', $row)) {
            $value = $row['is_online'];
            if ($value === null) {
                return null;
            }

            return (bool) $value;
        }

        if (($row['is_support_chat'] ?? false) === true) {
            return true;
        }

        if (($row['type'] ?? '') !== 'direct') {
            return null;
        }
        $peerId = (int) ($row['direct_peer']['id'] ?? 0);
        if ($peerId <= 0) {
            return null;
        }

        $payload = Cache::get('messenger_presence:'.$peerId);
        if (! is_array($payload)) {
            return false;
        }
        $ts = (int) ($payload['ts'] ?? 0);
        if ($ts <= 0) {
            return false;
        }
        $ttl = max(15, (int) config('messenger.presence_ttl_seconds', 60));

        return (time() - $ts) <= $ttl;
    }

    public function render()
    {
        return view('livewire.messenger.messenger-right-rail');
    }
}
