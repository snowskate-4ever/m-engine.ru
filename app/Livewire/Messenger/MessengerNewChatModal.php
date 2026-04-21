<?php

declare(strict_types=1);

namespace App\Livewire\Messenger;

use App\Models\AiServerModel;
use App\Models\User;
use App\Models\UserAiConnection;
use App\Services\Messenger\MessengerService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

final class MessengerNewChatModal extends Component
{
    public bool $open = false;

    public string $createType = 'direct';

    public string $directUserId = '';

    public string $selectedPeerLabel = '';

    public string $peerSearch = '';

    /** @var list<array{id: int, name: string, email: string}> */
    public array $peerResults = [];

    public string $groupTitle = '';

    public string $groupUserIds = '';

    /** @var list<array{id: int, label: string}> */
    public array $aiServerModels = [];

    /** @var list<array{id: int, label: string}> */
    public array $aiConnections = [];

    public string $aiTitle = '';

    public string $aiSource = 'server';

    public string $aiServerModelId = '';

    public string $aiConnectionId = '';

    #[On('messenger-open-new-chat')]
    public function openModal(): void
    {
        $this->resetForm();
        $this->resetValidation();
        $this->loadAiOptions();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    public function updatedPeerSearch(): void
    {
        $this->peerResults = $this->searchPeers($this->peerSearch);
    }

    public function updatedCreateType(): void
    {
        $this->peerResults = [];
        if ($this->createType === 'direct') {
            $this->peerResults = $this->searchPeers($this->peerSearch);
        }
    }

    public function selectPeer(int $userId): void
    {
        $this->directUserId = (string) $userId;
        $row = collect($this->peerResults)->firstWhere('id', $userId);
        $this->selectedPeerLabel = is_array($row)
            ? trim((string) ($row['name'] ?? '').' · '.(string) ($row['email'] ?? ''))
            : '';
    }

    private function resetForm(): void
    {
        $this->reset([
            'createType',
            'directUserId',
            'selectedPeerLabel',
            'peerSearch',
            'peerResults',
            'groupTitle',
            'groupUserIds',
            'aiTitle',
            'aiServerModelId',
            'aiConnectionId',
        ]);
        $this->createType = 'direct';
        $this->aiSource = 'server';
    }

    private function loadAiOptions(): void
    {
        if (! config('ai.enabled')) {
            $this->aiServerModels = [];
            $this->aiConnections = [];

            return;
        }

        $user = Auth::user();
        $this->aiServerModels = AiServerModel::query()
            ->where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (AiServerModel $m) => [
                'id' => $m->id,
                'label' => $m->display_name.' ('.$m->vendor_model_id.')',
            ])
            ->all();

        $this->aiConnections = UserAiConnection::query()
            ->where('user_id', $user->id)
            ->where('enabled', true)
            ->orderByDesc('id')
            ->get()
            ->map(static fn (UserAiConnection $c) => [
                'id' => $c->id,
                'label' => $c->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    private function searchPeers(string $raw): array
    {
        $query = trim($raw);
        if ($query === '') {
            return [];
        }

        $me = (int) Auth::id();
        $escaped = addcslashes($query, '%_\\');
        $like = '%'.$escaped.'%';
        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $users = User::query()
            ->where('id', '!=', $me)
            ->where(function (Builder $outer) use ($like, $query, $terms): void {
                $outer->where('name', 'LIKE', $like)
                    ->orWhere('email', 'LIKE', $like);
                if (ctype_digit($query)) {
                    $outer->orWhere('id', (int) $query);
                }
                if (count($terms) >= 2) {
                    $outer->orWhere(function (Builder $inner) use ($terms): void {
                        foreach ($terms as $t) {
                            if ($t === '') {
                                continue;
                            }
                            $inner->where('name', 'LIKE', '%'.addcslashes($t, '%_\\').'%');
                        }
                    });
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return $users
            ->map(static fn (User $u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
            ])
            ->all();
    }

    public function createChat(MessengerService $messenger): void
    {
        $this->validate([
            'createType' => ['required', 'string', 'in:direct,group,ai'],
            'directUserId' => ['required_if:createType,direct', 'nullable', 'integer', 'exists:users,id'],
            'groupTitle' => ['required_if:createType,group', 'nullable', 'string', 'max:255'],
            'groupUserIds' => ['nullable', 'string', 'max:2000'],
            'aiTitle' => ['required_if:createType,ai', 'nullable', 'string', 'max:255'],
            'aiSource' => ['required_if:createType,ai', 'nullable', 'string', 'in:server,byok'],
            'aiServerModelId' => [
                Rule::requiredIf(fn () => $this->createType === 'ai' && $this->aiSource === 'server'),
                'nullable',
                'integer',
                'min:1',
            ],
            'aiConnectionId' => [
                Rule::requiredIf(fn () => $this->createType === 'ai' && $this->aiSource === 'byok'),
                'nullable',
                'integer',
                'min:1',
            ],
        ], [], [
            'directUserId' => __('ui.messenger.peer_user_id'),
            'groupTitle' => __('ui.messenger.group_title'),
            'aiTitle' => __('ui.messenger.ai_chat_title'),
        ]);

        $user = Auth::user();

        if ($this->createType === 'direct') {
            if ((int) $this->directUserId === (int) $user->id) {
                $this->addError('directUserId', __('ui.messenger.cannot_chat_self'));

                return;
            }
            $data = [
                'type' => 'direct',
                'user_id' => (int) $this->directUserId,
            ];
        } elseif ($this->createType === 'group') {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', preg_split('/[\s,]+/', trim($this->groupUserIds))),
                fn (int $id) => $id > 0 && $id !== (int) $user->id,
            )));

            foreach ($ids as $id) {
                if (! User::query()->whereKey($id)->exists()) {
                    $this->addError('groupUserIds', __('ui.messenger.invalid_user_id'));

                    return;
                }
            }

            $data = [
                'type' => 'group',
                'title' => trim($this->groupTitle),
                'user_ids' => $ids,
            ];
        } elseif ($this->createType === 'ai') {
            if (! config('ai.enabled')) {
                $this->addError('createType', __('ui.messenger.ai_disabled'));

                return;
            }

            $data = [
                'type' => 'ai',
                'title' => trim($this->aiTitle),
                'ai_server_model_id' => $this->aiSource === 'server' ? (int) $this->aiServerModelId : null,
                'user_ai_connection_id' => $this->aiSource === 'byok' ? (int) $this->aiConnectionId : null,
            ];
        } else {
            return;
        }

        try {
            $conversation = $messenger->createConversation($user, $data);
        } catch (ValidationException $e) {
            throw $e;
        }

        $newId = $conversation->id;
        $this->resetForm();
        $this->open = false;

        $this->dispatch('messenger-rail-select-chat', conversationId: $newId);
        $this->dispatch('messenger-conversations-refresh');

        if (request()->routeIs(['messenger.index', 'messenger.show'])) {
            $this->redirect(route('messenger.show', $conversation), navigate: true);
        } else {
            $this->js("window.dispatchEvent(new CustomEvent('messenger-float-open'));");
        }
    }

    public function render()
    {
        return view('livewire.messenger.messenger-new-chat-modal');
    }
}
