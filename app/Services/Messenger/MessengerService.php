<?php

declare(strict_types=1);

namespace App\Services\Messenger;

use App\Enums\ConversationRole;
use App\Enums\ConversationType;
use App\Enums\MessageKind;
use App\Events\Messenger\ConversationRetentionUpdated;
use App\Events\Messenger\MessageSent;
use App\Events\Messenger\MessengerReadUpdated;
use App\Http\ApiErrorResponse;
use App\Jobs\ProcessAiChatReplyJob;
use App\Models\AiServerModel;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessengerUserPreference;
use App\Models\User;
use App\Models\UserAiConnection;
use App\Services\Ai\AiServerQuotaDeniedException;
use App\Services\Ai\AiServerQuotaService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

final class MessengerService
{
    public function __construct(
        private readonly AiServerQuotaService $aiQuota,
    ) {}

    public function getOrCreatePreferences(User $user): MessengerUserPreference
    {
        return MessengerUserPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['push_enabled' => true],
        );
    }

    /**
     * @return array{push_enabled: bool}
     */
    public function preferencesToArray(User $user): array
    {
        $p = $this->getOrCreatePreferences($user);

        return [
            'push_enabled' => $p->push_enabled,
        ];
    }

    public function updatePreferences(User $user, bool $pushEnabled): MessengerUserPreference
    {
        $p = $this->getOrCreatePreferences($user);
        $p->push_enabled = $pushEnabled;
        $p->save();

        return $p;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listConversationsSummary(User $user): array
    {
        $conversations = $user->conversations()
            ->with([
                'latestMessage.user:id,name',
                'latestMessage.attachments',
            ])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('conversations.updated_at')
            ->get();

        $out = [];
        foreach ($conversations as $conversation) {
            $out[] = $this->conversationToSummaryArray($conversation, $user);
        }

        return $out;
    }

    public function createConversation(User $user, array $data): Conversation
    {
        $type = ConversationType::from($data['type']);

        return match ($type) {
            ConversationType::Direct => $this->createDirectConversation($user, (int) $data['user_id']),
            ConversationType::Group => $this->createGroupConversation(
                $user,
                (string) $data['title'],
                array_map('intval', $data['user_ids'] ?? []),
            ),
            ConversationType::Ai => $this->createAiConversation($user, $data),
        };
    }

    /**
     * @param  array{title?: string|null, retention_days?: int|null, add_user_ids?: list<int>}  $data
     */
    public function updateConversation(User $user, Conversation $conversation, array $data): Conversation
    {
        $this->membershipOrAbort($user, $conversation);

        if (array_key_exists('retention_days', $data)) {
            $this->assertCanChangeRetention($user, $conversation);
            $old = $conversation->retention_days;
            $conversation->retention_days = $data['retention_days'];
            $conversation->save();

            if ($old !== $conversation->retention_days) {
                $notifyPeer = $conversation->type === ConversationType::Direct
                    ? $this->otherDirectPeerId($conversation, $user)
                    : null;
                event(new ConversationRetentionUpdated(
                    $conversation,
                    $user,
                    $notifyPeer,
                    $conversation->retention_days,
                ));
            }
        }

        if (array_key_exists('title', $data)) {
            if ($conversation->type !== ConversationType::Group) {
                throw ValidationException::withMessages(['title' => ['Only group conversations have a title.']]);
            }
            $this->assertGroupOwner($user, $conversation);
            $conversation->title = $data['title'];
            $conversation->save();
        }

        if (! empty($data['add_user_ids']) && is_array($data['add_user_ids'])) {
            $this->assertGroupOwner($user, $conversation);
            if ($conversation->type !== ConversationType::Group) {
                throw ValidationException::withMessages(['add_user_ids' => ['Only group conversations accept new members.']]);
            }
            $this->attachNewGroupMembers($conversation, $data['add_user_ids']);
        }

        return $conversation->fresh();
    }

    /**
     * @param  array{notifications_muted: bool, mute_until?: string|null}  $data
     */
    public function updateConversationNotifications(User $user, Conversation $conversation, array $data): ConversationUser
    {
        $membership = $this->membershipOrAbort($user, $conversation);
        $membership->notifications_muted = $data['notifications_muted'];
        $membership->mute_until = isset($data['mute_until']) && $data['mute_until'] !== ''
            ? $data['mute_until']
            : null;
        $membership->save();

        return $membership->fresh();
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{has_more: bool, next_before_id: ?int, next_after_id: ?int}}
     */
    public function listMessages(
        User $user,
        Conversation $conversation,
        ?int $beforeId,
        ?int $afterId,
        int $perPage,
    ): array {
        $this->membershipOrAbort($user, $conversation);

        $perPage = min(max($perPage, 1), 100);
        $query = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with(['user:id,name', 'attachments']);

        if ($afterId !== null) {
            $messages = (clone $query)->where('id', '>', $afterId)->orderBy('id')->limit($perPage + 1)->get();
            $hasMore = $messages->count() > $perPage;
            $messages = $messages->take($perPage);
            $nextAfterId = $messages->last()?->id;

            return [
                'data' => $messages->map(fn (Message $m) => $this->messageToArray($m))->values()->all(),
                'meta' => [
                    'has_more' => $hasMore,
                    'next_before_id' => null,
                    'next_after_id' => $hasMore ? $nextAfterId : null,
                ],
            ];
        }

        if ($beforeId !== null) {
            $messages = (clone $query)->where('id', '<', $beforeId)->orderByDesc('id')->limit($perPage + 1)->get();
            $hasMore = $messages->count() > $perPage;
            $messages = $messages->take($perPage)->sortBy('id')->values();
            $nextBeforeId = $messages->first()?->id;

            return [
                'data' => $messages->map(fn (Message $m) => $this->messageToArray($m))->values()->all(),
                'meta' => [
                    'has_more' => $hasMore,
                    'next_before_id' => $hasMore ? $nextBeforeId : null,
                    'next_after_id' => null,
                ],
            ];
        }

        $messages = (clone $query)->orderByDesc('id')->limit($perPage + 1)->get();
        $hasMore = $messages->count() > $perPage;
        $messages = $messages->take($perPage)->sortBy('id')->values();
        $nextBeforeId = $messages->first()?->id;

        return [
            'data' => $messages->map(fn (Message $m) => $this->messageToArray($m))->values()->all(),
            'meta' => [
                'has_more' => $hasMore,
                'next_before_id' => $hasMore ? $nextBeforeId : null,
                'next_after_id' => null,
            ],
        ];
    }

    /**
     * @param  array{
     *     body?: string|null,
     *     client_message_id?: string|null,
     *     forward_from_message_id?: int|null
     * }  $data
     * @param  list<UploadedFile>  $files
     */
    public function sendMessage(User $user, Conversation $conversation, array $data, array $files = []): Message
    {
        $this->membershipOrAbort($user, $conversation);

        $clientMessageId = $data['client_message_id'] ?? null;
        if ($clientMessageId !== null && $clientMessageId !== '') {
            $existing = Message::query()->where('client_message_id', $clientMessageId)->first();
            if ($existing !== null) {
                if ($existing->conversation_id !== $conversation->id) {
                    throw ValidationException::withMessages([
                        'client_message_id' => ['This id was already used in another conversation.'],
                    ]);
                }
                $existing->load(['user:id,name', 'attachments']);

                return $existing;
            }
        }

        $forwardId = isset($data['forward_from_message_id']) ? (int) $data['forward_from_message_id'] : null;
        $body = isset($data['body']) ? (string) $data['body'] : '';

        if ($forwardId !== null) {
            return $this->sendForwardedMessage($user, $conversation, $forwardId, $body, $clientMessageId, $files);
        }

        if ($body === '' && $files === []) {
            throw ValidationException::withMessages([
                'body' => ['Provide text, attachments, or forward_from_message_id.'],
            ]);
        }

        $this->validateAttachments($files);

        $this->assertOutgoingAiAllowed($user, $conversation, $body, $files, $forwardId);

        return DB::transaction(function () use ($user, $conversation, $body, $files, $clientMessageId) {
            $kind = $files !== [] ? MessageKind::File : MessageKind::Text;
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'kind' => $kind,
                'body' => $body !== '' ? $body : null,
                'is_forward' => false,
                'client_message_id' => $clientMessageId,
            ]);
            $this->storeAttachmentsForMessage($message, $files);
            $conversation->touch();
            $message->load(['user:id,name', 'attachments']);

            if ($this->shouldDispatchAiReply($conversation, $message, $files)
                && $conversation->user_ai_connection_id !== null
                && $conversation->ai_server_model_id === null) {
                $perMin = (int) config('ai.byok.max_requests_per_minute', 0);
                if ($perMin > 0) {
                    RateLimiter::hit('byok-ai:'.$user->id, 60);
                }
            }

            DB::afterCommit(function () use ($message, $conversation, $files) {
                broadcast(new MessageSent($message));
                if ($this->shouldDispatchAiReply($conversation, $message, $files)) {
                    ProcessAiChatReplyJob::dispatch($message->id);
                }
            });

            return $message;
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    /**
     * @param  list<UploadedFile>  $files
     */
    private function assertOutgoingAiAllowed(
        User $user,
        Conversation $conversation,
        string $body,
        array $files,
        ?int $forwardId,
    ): void {
        if ($forwardId !== null) {
            return;
        }
        if (! config('ai.enabled')) {
            return;
        }
        if ($conversation->type !== ConversationType::Ai) {
            return;
        }
        if ($body === '' || $files !== []) {
            return;
        }

        if ($conversation->user_ai_connection_id !== null && $conversation->ai_server_model_id === null) {
            $perMin = (int) config('ai.byok.max_requests_per_minute', 0);
            if ($perMin > 0) {
                $key = 'byok-ai:'.$user->id;
                if (RateLimiter::tooManyAttempts($key, $perMin)) {
                    throw new HttpResponseException(
                        ApiErrorResponse::json(
                            'quota_exceeded',
                            (string) __('ui.messenger.ai_byok_rate_limited'),
                            402,
                        ),
                    );
                }
            }

            return;
        }

        if ($conversation->ai_server_model_id === null) {
            return;
        }

        try {
            $this->aiQuota->assertMayConsumeServerTokensThisMonth($user);
            $this->aiQuota->assertMayConsumeServerAiRequest($user);
            $this->aiQuota->assertServerModelAllowedForPlan($user, (int) $conversation->ai_server_model_id);
        } catch (AiServerQuotaDeniedException $e) {
            throw new HttpResponseException(ApiErrorResponse::fromAiServerQuotaDenied($e));
        }
    }

    private function shouldDispatchAiReply(Conversation $conversation, Message $message, array $files): bool
    {
        if (! config('ai.enabled')) {
            return false;
        }
        if ($conversation->type !== ConversationType::Ai) {
            return false;
        }
        if ($message->user_id === null) {
            return false;
        }
        if ($message->is_forward) {
            return false;
        }
        if ($message->kind !== MessageKind::Text) {
            return false;
        }
        if ($files !== []) {
            return false;
        }

        return true;
    }

    public function markRead(User $user, Conversation $conversation, int $messageId): void
    {
        $this->membershipOrAbort($user, $conversation);

        $exists = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', $messageId)
            ->exists();
        if (! $exists) {
            throw ValidationException::withMessages(['message_id' => ['Message not found in this conversation.']]);
        }

        $membership = ConversationUser::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $current = $membership->last_read_message_id ?? 0;
        if ($messageId > $current) {
            $membership->last_read_message_id = $messageId;
            $membership->save();
            broadcast(new MessengerReadUpdated($conversation->id, $user->id, $messageId));
        }
    }

    public function conversationToDetailArray(Conversation $conversation, User $viewer): array
    {
        $this->membershipOrAbort($viewer, $conversation);

        $base = $this->conversationToSummaryArray($conversation, $viewer);
        $base['participants'] = $conversation->participants()
            ->get(['users.id', 'users.name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        return $base;
    }

    public function messageToPublicArray(Message $message): array
    {
        $message->loadMissing(['user:id,name', 'attachments']);

        return $this->messageToArray($message);
    }

    public function membershipOrAbort(User $user, Conversation $conversation): ConversationUser
    {
        $m = ConversationUser::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();
        if ($m === null) {
            abort(404);
        }

        return $m;
    }

    private function createDirectConversation(User $user, int $otherUserId): Conversation
    {
        abort_if($otherUserId === $user->id, 422, 'Cannot start a direct chat with yourself.');
        $other = User::query()->find($otherUserId);
        abort_if($other === null, 404);

        $minId = min($user->id, $otherUserId);
        $maxId = max($user->id, $otherUserId);

        $existing = Conversation::query()
            ->where('type', ConversationType::Direct)
            ->where('direct_peer_min_id', $minId)
            ->where('direct_peer_max_id', $maxId)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $minId, $maxId) {
            $conversation = Conversation::create([
                'type' => ConversationType::Direct,
                'title' => null,
                'created_by_user_id' => $user->id,
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

    /**
     * @param  list<int>  $userIds
     */
    private function createGroupConversation(User $creator, string $title, array $userIds): Conversation
    {
        $title = trim($title);
        if ($title === '') {
            throw ValidationException::withMessages(['title' => ['Title is required for a group.']]);
        }

        $ids = collect($userIds)->map(fn (int $id) => $id)->unique()->filter(fn (int $id) => $id !== $creator->id)->values();
        $foundCount = User::query()->whereIn('id', $ids->all())->count();
        if ($foundCount !== $ids->count()) {
            abort(404, 'One or more users were not found.');
        }

        return DB::transaction(function () use ($creator, $title, $ids) {
            $conversation = Conversation::create([
                'type' => ConversationType::Group,
                'title' => $title,
                'created_by_user_id' => $creator->id,
            ]);
            $now = now();
            $attach = [
                $creator->id => [
                    'role' => ConversationRole::Owner->value,
                    'joined_at' => $now,
                ],
            ];
            foreach ($ids as $uid) {
                $attach[$uid] = [
                    'role' => ConversationRole::Member->value,
                    'joined_at' => $now,
                ];
            }
            $conversation->participants()->attach($attach);

            return $conversation;
        });
    }

    /**
     * @param  array{
     *     title?: string,
     *     ai_server_model_id?: int|null,
     *     user_ai_connection_id?: int|null
     * }  $data
     */
    private function createAiConversation(User $user, array $data): Conversation
    {
        if (! config('ai.enabled')) {
            throw ValidationException::withMessages(['type' => ['AI is disabled.']]);
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => ['Title is required for AI chats.']]);
        }

        $serverId = isset($data['ai_server_model_id']) ? (int) $data['ai_server_model_id'] : 0;
        $connId = isset($data['user_ai_connection_id']) ? (int) $data['user_ai_connection_id'] : 0;

        $hasServer = $serverId > 0;
        $hasConn = $connId > 0;

        if ($hasServer === $hasConn) {
            throw ValidationException::withMessages([
                'ai_server_model_id' => ['Provide exactly one of ai_server_model_id or user_ai_connection_id.'],
            ]);
        }

        $this->aiQuota->assertMayCreateAnotherAiChat($user);

        if ($hasServer) {
            $model = AiServerModel::query()
                ->whereKey($serverId)
                ->where('is_active', true)
                ->whereHas('provider', fn ($q) => $q->where('is_active', true))
                ->first();
            if ($model === null) {
                throw ValidationException::withMessages(['ai_server_model_id' => ['Invalid or inactive AI model.']]);
            }

            return DB::transaction(function () use ($user, $title, $model) {
                $conversation = Conversation::create([
                    'type' => ConversationType::Ai,
                    'title' => $title,
                    'created_by_user_id' => $user->id,
                    'ai_server_model_id' => $model->id,
                    'user_ai_connection_id' => null,
                ]);
                $conversation->participants()->attach($user->id, [
                    'role' => ConversationRole::Owner->value,
                    'joined_at' => now(),
                ]);

                return $conversation;
            });
        }

        $connection = UserAiConnection::query()
            ->whereKey($connId)
            ->where('user_id', $user->id)
            ->where('enabled', true)
            ->first();
        if ($connection === null) {
            throw ValidationException::withMessages(['user_ai_connection_id' => ['Invalid or disabled connection.']]);
        }

        return DB::transaction(function () use ($user, $title, $connection) {
            $conversation = Conversation::create([
                'type' => ConversationType::Ai,
                'title' => $title,
                'created_by_user_id' => $user->id,
                'ai_server_model_id' => null,
                'user_ai_connection_id' => $connection->id,
            ]);
            $conversation->participants()->attach($user->id, [
                'role' => ConversationRole::Owner->value,
                'joined_at' => now(),
            ]);

            return $conversation;
        });
    }

    /**
     * @param  list<int>  $userIds
     */
    private function attachNewGroupMembers(Conversation $conversation, array $userIds): void
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->unique()->values();
        $existing = ConversationUser::query()
            ->where('conversation_id', $conversation->id)
            ->pluck('user_id')
            ->all();
        $toAdd = $ids->diff($existing)->values();
        if ($toAdd->isEmpty()) {
            return;
        }

        $foundCount = User::query()->whereIn('id', $toAdd->all())->count();
        if ($foundCount !== $toAdd->count()) {
            abort(404, 'One or more users were not found.');
        }

        $now = now();
        foreach ($toAdd as $uid) {
            $conversation->participants()->attach($uid, [
                'role' => ConversationRole::Member->value,
                'joined_at' => $now,
            ]);
        }
    }

    private function assertCanChangeRetention(User $user, Conversation $conversation): void
    {
        if ($conversation->type === ConversationType::Group) {
            $this->assertGroupOwner($user, $conversation);

            return;
        }
        if ($conversation->type === ConversationType::Direct) {
            return;
        }
        if ($conversation->type === ConversationType::Ai) {
            throw ValidationException::withMessages(['retention_days' => ['Retention for AI chats is not supported yet.']]);
        }
    }

    private function assertGroupOwner(User $user, Conversation $conversation): void
    {
        $m = $this->membershipOrAbort($user, $conversation);
        if ($m->role !== ConversationRole::Owner) {
            abort(403, 'Only the group owner can perform this action.');
        }
    }

    private function otherDirectPeerId(Conversation $conversation, User $user): ?int
    {
        if ($conversation->type !== ConversationType::Direct) {
            return null;
        }
        if ($conversation->direct_peer_min_id === $user->id) {
            return $conversation->direct_peer_max_id;
        }
        if ($conversation->direct_peer_max_id === $user->id) {
            return $conversation->direct_peer_min_id;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationToSummaryArray(Conversation $conversation, User $viewer): array
    {
        $membership = $this->membershipOrAbort($viewer, $conversation);
        $lastRead = (int) ($membership->last_read_message_id ?? 0);
        $unread = $this->unreadCount($conversation, $viewer, $lastRead);

        $row = [
            'id' => $conversation->id,
            'type' => $conversation->type->value,
            'title' => $conversation->title,
            'retention_days' => $conversation->retention_days,
            'created_at' => $conversation->created_at?->toIso8601String(),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
            'unread_count' => $unread,
            'notifications_muted' => (bool) $membership->notifications_muted,
            'mute_until' => $membership->mute_until?->toIso8601String(),
            'last_message' => null,
        ];

        if ($conversation->type === ConversationType::Direct) {
            $otherId = $this->otherDirectPeerId($conversation, $viewer);
            if ($otherId !== null) {
                $other = User::query()->find($otherId);
                $row['direct_peer'] = $other !== null
                    ? ['id' => $other->id, 'name' => $other->name]
                    : ['id' => $otherId, 'name' => null];
            }
        }

        if ($conversation->type === ConversationType::Ai) {
            $row['ai_server_model_id'] = $conversation->ai_server_model_id;
            $row['user_ai_connection_id'] = $conversation->user_ai_connection_id;
        }

        $latest = $conversation->relationLoaded('latestMessage')
            ? $conversation->latestMessage
            : $conversation->latestMessage()->with(['user:id,name', 'attachments'])->first();
        if ($latest !== null) {
            $row['last_message'] = $this->messageToArray($latest);
        }

        return $row;
    }

    private function unreadCount(Conversation $conversation, User $viewer, int $lastReadId): int
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', '>', $lastReadId)
            ->where(function ($q) use ($viewer) {
                $q->whereNull('user_id')->orWhere('user_id', '!=', $viewer->id);
            })
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function messageToArray(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'author' => $this->messageAuthorPayload($message),
            'kind' => $this->messageKindToApiString($message),
            'body' => $this->messageBodyToApiString($message),
            'is_forward' => (bool) $message->is_forward,
            'forwarded_from_message_id' => $message->forwarded_from_message_id,
            'forward_snapshot' => $this->messageForwardSnapshotToArray($message),
            'client_message_id' => $this->messageClientMessageIdToString($message),
            'created_at' => $message->created_at?->toIso8601String(),
            'attachments' => $message->attachments->map(fn (MessageAttachment $a) => $this->attachmentToApiArray($a))->values()->all(),
        ];
    }

    /**
     * @return array{id: int, name: string|null}|null
     */
    private function messageAuthorPayload(Message $message): ?array
    {
        $user = $message->user;
        if ($user === null) {
            return null;
        }
        $name = $user->name;
        if (! is_string($name)) {
            $name = is_scalar($name) ? (string) $name : '';
        }
        $name = $name === '' ? null : $name;

        return ['id' => (int) $user->id, 'name' => $name];
    }

    private function messageKindToApiString(Message $message): string
    {
        try {
            $k = $message->getAttribute('kind');
            if ($k instanceof MessageKind) {
                return $k->value;
            }
            if (is_string($k) && $k !== '') {
                return in_array($k, ['text', 'file', 'system'], true) ? $k : MessageKind::Text->value;
            }
        } catch (\Throwable) {
        }

        return MessageKind::Text->value;
    }

    private function messageBodyToApiString(Message $message): ?string
    {
        $body = $message->getAttribute('body');
        if ($body === null) {
            return null;
        }
        if (is_string($body)) {
            return $body;
        }
        if (is_scalar($body)) {
            return (string) $body;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function messageForwardSnapshotToArray(Message $message): ?array
    {
        try {
            $v = $message->getAttribute('forward_snapshot');
            if ($v === null) {
                return null;
            }
            if (is_array($v)) {
                return $v;
            }
            if (is_string($v) && $v !== '') {
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function messageClientMessageIdToString(Message $message): ?string
    {
        $v = $message->getAttribute('client_message_id');
        if ($v === null) {
            return null;
        }
        if (is_string($v)) {
            return $v;
        }
        if (is_object($v) && method_exists($v, '__toString')) {
            return (string) $v;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function attachmentToApiArray(MessageAttachment $a): array
    {
        $path = $a->path;
        $path = is_string($path) ? $path : '';
        $disk = $a->disk;
        $disk = is_string($disk) && $disk !== '' ? $disk : 'local';
        $originalName = $a->original_name;
        $originalName = is_string($originalName) ? $originalName : null;
        $mime = $a->mime;
        $mime = is_string($mime) ? $mime : null;
        $size = is_numeric($a->size) ? (int) $a->size : 0;

        $downloadUrl = '';
        try {
            $downloadUrl = $this->attachmentDownloadUrl($a);
        } catch (\Throwable) {
            $downloadUrl = '';
        }

        return [
            'id' => $a->id,
            'path' => $path,
            'disk' => $disk,
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => $size,
            'download_url' => $downloadUrl,
        ];
    }

    private function attachmentDownloadUrl(MessageAttachment $attachment): string
    {
        $ttl = (int) config('messenger.attachment_download_ttl_minutes', 60);

        return URL::temporarySignedRoute(
            'api.messenger.attachment.download',
            now()->addMinutes(max(1, $ttl)),
            ['attachment' => $attachment->id],
        );
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function sendForwardedMessage(
        User $user,
        Conversation $conversation,
        int $forwardFromMessageId,
        string $body,
        ?string $clientMessageId,
        array $files,
    ): Message {
        $source = Message::query()->with(['user:id,name', 'attachments', 'conversation.participants'])->find($forwardFromMessageId);
        if ($source === null) {
            throw ValidationException::withMessages(['forward_from_message_id' => ['Source message not found.']]);
        }
        $this->membershipOrAbort($user, $source->conversation);
        if ($files !== []) {
            throw ValidationException::withMessages(['attachments' => ['Attachments are not allowed when forwarding.']]);
        }

        $snapshot = $this->buildForwardSnapshot($source);

        return DB::transaction(function () use ($user, $conversation, $source, $snapshot, $body, $clientMessageId) {
            $caption = $body !== '' ? $body : null;
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'kind' => MessageKind::Text,
                'body' => $caption,
                'is_forward' => true,
                'forwarded_from_message_id' => $source->id,
                'forward_snapshot' => $snapshot,
                'client_message_id' => $clientMessageId,
            ]);
            $conversation->touch();
            $message->load(['user:id,name', 'attachments']);

            DB::afterCommit(function () use ($message) {
                broadcast(new MessageSent($message));
            });

            return $message;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildForwardSnapshot(Message $source): array
    {
        return [
            'body' => $source->body,
            'kind' => $source->kind->value,
            'source_conversation_id' => $source->conversation_id,
            'author' => $source->user !== null
                ? ['id' => $source->user->id, 'name' => $source->user->name]
                : null,
            'attachments' => $source->attachments->map(fn (MessageAttachment $a) => [
                'original_name' => $a->original_name,
                'mime' => $a->mime,
                'size' => $a->size,
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function validateAttachments(array $files): void
    {
        $maxCount = config('messenger.max_attachments_per_message');
        if ($maxCount !== null && count($files) > $maxCount) {
            throw ValidationException::withMessages([
                'attachments' => ["Maximum {$maxCount} attachment(s) per message."],
            ]);
        }

        $maxBytes = config('messenger.max_attachment_size_bytes');
        $allowedMimes = config('messenger.allowed_mime_types');
        if (is_array($allowedMimes) && $allowedMimes === []) {
            $allowedMimes = null;
        }

        foreach ($files as $i => $file) {
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    "attachments.{$i}" => ['Invalid upload.'],
                ]);
            }
            if ($maxBytes !== null && $maxBytes > 0 && $file->getSize() > $maxBytes) {
                throw ValidationException::withMessages([
                    "attachments.{$i}" => ['File exceeds maximum allowed size.'],
                ]);
            }
            if (is_array($allowedMimes) && $allowedMimes !== null) {
                $mime = $file->getMimeType() ?? $file->getClientMimeType();
                if ($mime !== null && ! in_array($mime, $allowedMimes, true)) {
                    throw ValidationException::withMessages([
                        "attachments.{$i}" => ['File type is not allowed.'],
                    ]);
                }
            }
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function storeAttachmentsForMessage(Message $message, array $files): void
    {
        $disk = config('filesystems.default', 'local');
        foreach ($files as $file) {
            $path = $file->store('messenger/attachments', $disk);
            MessageAttachment::create([
                'message_id' => $message->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }
    }
}
