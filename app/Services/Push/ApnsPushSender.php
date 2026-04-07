<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\DevicePushToken;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Apple Push Notification service (HTTP/2, token-based auth).
 * When {@see config('messenger.apns')} is incomplete, sends are skipped.
 */
final class ApnsPushSender
{
    public function isConfigured(): bool
    {
        $c = config('messenger.apns', []);

        if (! is_array($c)) {
            return false;
        }

        $team = isset($c['team_id']) && is_string($c['team_id']) ? trim($c['team_id']) : '';
        $kid = isset($c['key_id']) && is_string($c['key_id']) ? trim($c['key_id']) : '';
        $bundle = isset($c['bundle_id']) && is_string($c['bundle_id']) ? trim($c['bundle_id']) : '';
        $path = isset($c['auth_key_path']) && is_string($c['auth_key_path']) ? trim($c['auth_key_path']) : '';

        if ($team === '' || $kid === '' || $bundle === '' || $path === '') {
            return false;
        }

        return is_readable($path);
    }

    /**
     * @param  array<string, mixed>  $data  String values recommended (mirrors FCM data)
     * @return bool True if accepted or skipped as unconfigured; false on retryable HTTP failure
     */
    public function sendToToken(DevicePushToken $token, string $title, string $body, array $data): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        $pem = @file_get_contents((string) config('messenger.apns.auth_key_path'));
        if ($pem === false || $pem === '') {
            Log::error('messenger.apns_auth_key_unreadable', ['path' => config('messenger.apns.auth_key_path')]);

            return false;
        }

        try {
            $jwt = $this->buildJwt($pem);
        } catch (InvalidArgumentException $e) {
            Log::error('messenger.apns_jwt_failed', ['message' => $e->getMessage()]);

            return false;
        }

        $deviceHex = $this->normalizeDeviceToken($token->token);
        if ($deviceHex === '') {
            Log::notice('messenger.apns_empty_device_token', ['token_id' => $token->id]);
            $token->delete();

            return true;
        }

        $host = config('messenger.apns.use_sandbox')
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';
        $url = $host.'/3/device/'.$deviceHex;

        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'sound' => 'default',
            ],
        ];
        foreach ($data as $k => $v) {
            $payload[(string) $k] = is_string($v) ? $v : (string) $v;
        }

        $timeout = (int) config('messenger.apns.timeout_seconds', 15);

        $response = Http::withOptions([
            'version' => 2.0,
        ])
            ->timeout($timeout)
            ->withHeaders([
                'authorization' => 'bearer '.$jwt,
                'apns-topic' => (string) config('messenger.apns.bundle_id'),
                'apns-push-type' => 'alert',
                'apns-priority' => '10',
                'apns-expiration' => '0',
            ])
            ->withBody((string) json_encode($payload, JSON_THROW_ON_ERROR), 'application/json')
            ->post($url);

        if ($response->status() === 200) {
            return true;
        }

        $this->handleErrorResponse($response->status(), $response->body(), $token);

        if ($response->serverError()) {
            return false;
        }

        return true;
    }

    private function buildJwt(string $privateKeyPem): string
    {
        $teamId = (string) config('messenger.apns.team_id');
        $keyId = (string) config('messenger.apns.key_id');

        return JWT::encode(
            [
                'iss' => $teamId,
                'iat' => time(),
            ],
            $privateKeyPem,
            'ES256',
            $keyId,
        );
    }

    private function normalizeDeviceToken(string $raw): string
    {
        $hexOnly = strtolower((string) preg_replace('/[^0-9a-fA-F]/', '', $raw));
        if (strlen($hexOnly) >= 64 && ctype_xdigit($hexOnly)) {
            return $hexOnly;
        }

        return '';
    }

    private function handleErrorResponse(int $status, string $body, DevicePushToken $token): void
    {
        $reason = null;
        if ($body !== '') {
            try {
                /** @var array<string, mixed> $json */
                $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (isset($json['reason']) && is_string($json['reason'])) {
                    $reason = $json['reason'];
                }
            } catch (\JsonException) {
            }
        }

        $dropReasons = ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic'];

        if ($status === 410 || ($status === 400 && $reason !== null && in_array($reason, $dropReasons, true))) {
            $token->delete();
        }

        Log::notice('messenger.apns_rejected', [
            'status' => $status,
            'reason' => $reason,
            'token_id' => $token->id,
            'body' => strlen($body) > 500 ? substr($body, 0, 500).'…' : $body,
        ]);
    }
}
