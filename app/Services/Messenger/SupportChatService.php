<?php

declare(strict_types=1);

namespace App\Services\Messenger;

use App\Enums\ConversationRole;
use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class SupportChatService
{
    public function resolveSupportUser(): ?User
    {
        $email = trim((string) config('support_chat.support_user_email'));
        if ($email === '') {
            return null;
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            return $existing;
        }
        if (! (bool) config('support_chat.auto_create_user', true)) {
            return null;
        }

        return User::query()->create([
            'name' => (string) config('support_chat.support_user_name', 'Поддержка'),
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
        ]);
    }

    public function ensureForUser(User $user): ?Conversation
    {
        $support = $this->resolveSupportUser();
        if ($support === null || $support->id === $user->id) {
            return null;
        }

        $existing = $this->findBetween($support, $user);
        if ($existing !== null) {
            return $existing;
        }

        $minId = min($support->id, $user->id);
        $maxId = max($support->id, $user->id);

        return DB::transaction(function () use ($support, $user, $minId, $maxId): Conversation {
            $conversation = Conversation::query()->create([
                'type' => ConversationType::Direct,
                'title' => null,
                'created_by_user_id' => $support->id,
                'direct_peer_min_id' => $minId,
                'direct_peer_max_id' => $maxId,
            ]);
            $now = now();
            $conversation->participants()->attach([
                $minId => [
                    'role' => ConversationRole::Member->value,
                    'joined_at' => $now,
                ],
                $maxId => [
                    'role' => ConversationRole::Member->value,
                    'joined_at' => $now,
                ],
            ]);

            return $conversation;
        });
    }

    public function findForUser(User $user): ?Conversation
    {
        $support = $this->resolveSupportUser();
        if ($support === null || $support->id === $user->id) {
            return null;
        }

        return $this->findBetween($support, $user);
    }

    public function isSupportConversation(Conversation $conversation): bool
    {
        if ($conversation->type !== ConversationType::Direct) {
            return false;
        }

        return $this->customerId($conversation) !== null;
    }

    public function customerId(Conversation $conversation): ?int
    {
        if ($conversation->type !== ConversationType::Direct) {
            return null;
        }

        $support = $this->resolveSupportUser();
        if ($support === null) {
            return null;
        }

        if ($conversation->direct_peer_min_id === $support->id) {
            return $conversation->direct_peer_max_id;
        }
        if ($conversation->direct_peer_max_id === $support->id) {
            return $conversation->direct_peer_min_id;
        }

        return null;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function listSupportConversations(): Collection
    {
        $support = $this->resolveSupportUser();
        if ($support === null) {
            return collect();
        }

        return Conversation::query()
            ->where('type', ConversationType::Direct)
            ->where(function ($q) use ($support): void {
                $q->where('direct_peer_min_id', $support->id)
                    ->orWhere('direct_peer_max_id', $support->id);
            })
            ->with(['latestMessage.user:id,name', 'latestMessage.attachments'])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    private function findBetween(User $a, User $b): ?Conversation
    {
        $min = min($a->id, $b->id);
        $max = max($a->id, $b->id);

        return Conversation::query()
            ->where('type', ConversationType::Direct)
            ->where('direct_peer_min_id', $min)
            ->where('direct_peer_max_id', $max)
            ->first();
    }
}
