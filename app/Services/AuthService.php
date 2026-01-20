<?php

namespace App\Services;

use App\Models\AuthAttempt;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function createAttempt(
        string $channel,
        string $channelType,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $metadata = []
    ): AuthAttempt {
        $attempt = AuthAttempt::create([
            'channel' => $channel,
            'channel_type' => $channelType,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
            'auth_token' => Str::random(64),
            'expires_at' => now()->addMinutes(config('auth_channels.default_expiry', 30)),
            'status' => 'pending'
        ]);

        Log::channel('auth')->info('Auth attempt created', [
            'attempt_id' => $attempt->id,
            'channel' => $attempt->channel,
            'type' => $attempt->channel_type,
            'ip' => $ipAddress
        ]);

        return $attempt;
    }

    public function processTelegramAuth(array $data, AuthAttempt $attempt): User
    {
        // Поиск пользователя по telegram_id или создание нового
        $user = User::firstOrCreate(
            ['telegram_id' => $data['telegram_id']],
            [
                'name' => $data['first_name'] . ' ' . ($data['last_name'] ?? ''),
                'email' => $data['telegram_id'] . '@telegram.user',
                'password' => Hash::make(Str::random(32)),
                'registration_channel' => 'telegram',
                'registration_metadata' => [
                    'username' => $data['username'] ?? null,
                    'chat_id' => $data['chat_id'] ?? null,
                    'language_code' => $data['language_code'] ?? null,
                ]
            ]
        );

        return $user;
    }

    public function processN8NAuth(array $data, AuthAttempt $attempt): User
    {
        // Логика обработки данных из N8N
        $userData = [
            'name' => $data['name'] ?? 'N8N User',
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? Str::random(32)),
            'registration_channel' => 'n8n_webhook',
            'registration_metadata' => [
                'workflow_id' => $data['workflow_id'] ?? null,
                'execution_id' => $data['execution_id'] ?? null,
                'node_type' => $data['node_type'] ?? null,
                'source_system' => $data['source_system'] ?? 'n8n'
            ]
        ];

        $user = User::create($userData);

        // Отправка уведомления в N8N обратно
        if ($webhookUrl = $attempt->metadata['callback_url'] ?? null) {
            $this->sendN8NCallback($webhookUrl, $user, $attempt);
        }

        return $user;
    }

    public function processWebAuth(array $data, AuthAttempt $attempt): User
    {
        // Стандартная веб авторизация
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $data['name'] ?? 'Web User',
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'registration_channel' => 'web',
                'registration_metadata' => [
                    'registration_source' => $data['source'] ?? 'direct'
                ]
            ]);
        }

        return $user;
    }

    public function generateApiToken(User $user): string
    {
        return $user->createToken(
            name: 'api-token',
            abilities: ['*'],
            expiresAt: now()->addDays(30)
        )->plainTextToken;
    }

    public function findAttemptByToken(string $token): ?AuthAttempt
    {
        return AuthAttempt::where('auth_token', $token)
            ->where('status', 'pending')
            ->first();
    }

    public function validateAttempt(AuthAttempt $attempt): bool
    {
        if ($attempt->isExpired()) {
            $attempt->markAsFailed();
            return false;
        }

        return $attempt->isPending();
    }

    private function sendN8NCallback(string $url, User $user, AuthAttempt $attempt): void
    {
        try {
            $response = Http::timeout(10)->post($url, [
                'event' => 'user_created',
                'user_id' => $user->id,
                'attempt_id' => $attempt->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'timestamp' => now()->toISOString(),
                'registration_channel' => $user->registration_channel
            ]);

            if ($response->successful()) {
                Log::channel('auth')->info('N8N callback sent successfully', [
                    'user_id' => $user->id,
                    'attempt_id' => $attempt->id,
                    'callback_url' => $url
                ]);
            } else {
                Log::channel('auth')->error('N8N callback failed', [
                    'user_id' => $user->id,
                    'attempt_id' => $attempt->id,
                    'callback_url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('auth')->error('N8N callback exception', [
                'user_id' => $user->id,
                'attempt_id' => $attempt->id,
                'callback_url' => $url,
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function cleanupExpiredAttempts(): int
    {
        $count = AuthAttempt::expired()
            ->whereIn('status', ['pending', 'expired'])
            ->update(['status' => 'expired']);

        Log::channel('auth')->info('Cleaned up expired auth attempts', [
            'count' => $count
        ]);

        return $count;
    }
}