<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PushPlatform;
use App\Models\ConversationUser;
use App\Models\DevicePushToken;
use App\Models\Message;
use App\Models\MessengerUserPreference;
use App\Services\Push\ApnsPushSender;
use App\Services\Push\FcmLegacyPushSender;
use App\Services\Push\MessengerPushPreview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendMessengerPushNotificationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $messageId) {}

    public function handle(FcmLegacyPushSender $fcm, ApnsPushSender $apns): void
    {
        $message = Message::query()->with(['user', 'attachments', 'conversation'])->find($this->messageId);
        if ($message === null) {
            return;
        }

        if (! $fcm->isConfigured() && ! $apns->isConfigured()) {
            Log::debug('messenger.push_skipped_no_transport', ['message_id' => $this->messageId]);

            return;
        }

        $conversationId = $message->conversation_id;
        $senderId = $message->user_id;

        $participantIds = ConversationUser::query()
            ->where('conversation_id', $conversationId)
            ->pluck('user_id')
            ->all();

        $preview = MessengerPushPreview::forMessage($message);
        $data = [
            'type' => 'messenger.message',
            'conversation_id' => (string) $conversationId,
            'message_id' => (string) $message->id,
        ];

        foreach ($participantIds as $userId) {
            if ($senderId !== null && (int) $userId === (int) $senderId) {
                continue;
            }

            if (! $this->userAcceptsPush((int) $userId, $conversationId)) {
                continue;
            }

            if ($this->shouldSkipPushForForegroundPresence((int) $userId, $conversationId)) {
                continue;
            }

            $tokens = DevicePushToken::query()->where('user_id', $userId)->get();
            foreach ($tokens as $token) {
                if ($token->platform === PushPlatform::Ios) {
                    if ($apns->isConfigured()) {
                        $apns->sendToToken($token, $preview['title'], $preview['body'], $data);
                    }
                } elseif ($fcm->isConfigured()) {
                    $fcm->sendToToken($token, $preview['title'], $preview['body'], $data);
                }
            }
        }
    }

    private function userAcceptsPush(int $userId, int $conversationId): bool
    {
        $pref = MessengerUserPreference::query()->where('user_id', $userId)->first();
        if ($pref !== null && ! $pref->push_enabled) {
            return false;
        }

        $membership = ConversationUser::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if ($membership === null) {
            return false;
        }

        if ($membership->notifications_muted) {
            return false;
        }

        if ($membership->mute_until !== null && $membership->mute_until->isFuture()) {
            return false;
        }

        return true;
    }

    private function shouldSkipPushForForegroundPresence(int $userId, int $conversationId): bool
    {
        $payload = Cache::get('messenger_presence:'.$userId);
        if (! is_array($payload)) {
            return false;
        }
        if ((int) ($payload['conversation_id'] ?? 0) !== $conversationId) {
            return false;
        }
        $ts = (int) ($payload['ts'] ?? 0);
        if ($ts < 1) {
            return false;
        }
        $ttl = max(15, (int) config('messenger.presence_ttl_seconds', 60));

        return (time() - $ts) <= ($ttl + 5);
    }
}
