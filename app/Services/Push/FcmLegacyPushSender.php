<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\DevicePushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Optional legacy FCM HTTP API (deprecated by Google, still used by many backends).
 * When {@see config('messenger.fcm.legacy_server_key')} is empty, sends are skipped.
 */
final class FcmLegacyPushSender
{
    public function isConfigured(): bool
    {
        $key = config('messenger.fcm.legacy_server_key');

        return is_string($key) && $key !== '';
    }

    /**
     * @return bool True if the message was accepted (or skipped as unconfigured); false if send failed in a retryable way.
     */
    public function sendToToken(DevicePushToken $token, string $title, string $body, array $data): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        $serverKey = config('messenger.fcm.legacy_server_key');

        $response = Http::timeout((int) config('messenger.fcm.timeout_seconds', 15))
            ->withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token->token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map(static fn ($v) => is_string($v) ? $v : (string) $v, $data),
                'priority' => 'high',
            ]);

        if (! $response->successful()) {
            Log::warning('messenger.fcm_legacy_http_error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $json = $response->json();
        if (! is_array($json)) {
            return true;
        }

        $failure = (int) ($json['failure'] ?? 0);
        if ($failure > 0 && isset($json['results'][0]['error'])) {
            $err = (string) $json['results'][0]['error'];
            if (in_array($err, ['NotRegistered', 'InvalidRegistration', 'MismatchSenderId'], true)) {
                $token->delete();
            }
            Log::notice('messenger.fcm_legacy_token_rejected', [
                'error' => $err,
                'token_id' => $token->id,
            ]);
        }

        return true;
    }
}
