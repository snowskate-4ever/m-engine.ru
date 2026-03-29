<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Enums\PushPlatform;
use App\Models\DevicePushToken;
use App\Models\MessengerUserPreference;
use App\Models\User;

/**
 * Push to all device tokens of a user (no per-conversation mute), respecting global messenger preference.
 */
final class UserDirectPushService
{
    public function __construct(
        private readonly FcmLegacyPushSender $fcm,
        private readonly ApnsPushSender $apns,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $pref = MessengerUserPreference::query()->where('user_id', $user->id)->first();
        if ($pref !== null && ! $pref->push_enabled) {
            return;
        }

        $tokens = DevicePushToken::query()->where('user_id', $user->id)->get();
        foreach ($tokens as $token) {
            if ($token->platform === PushPlatform::Ios) {
                if ($this->apns->isConfigured()) {
                    $this->apns->sendToToken($token, $title, $body, $data);
                }
            } elseif ($this->fcm->isConfigured()) {
                $this->fcm->sendToToken($token, $title, $body, $data);
            }
        }
    }
}
