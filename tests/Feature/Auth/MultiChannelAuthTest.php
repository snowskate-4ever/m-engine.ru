<?php

namespace Tests\Feature\Auth;

use App\Models\AuthAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiChannelAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_authentication_creates_attempt_and_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth', [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'X-Auth-Channel-Type' => 'web',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user' => ['id', 'name', 'email'],
                'token',
                'attempt_id'
            ]);

        $this->assertDatabaseHas('auth_attempts', [
            'channel_type' => 'web',
            'status' => 'success',
            'user_id' => $user->id,
        ]);
    }

    public function test_web_authentication_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ], [
            'X-Auth-Channel-Type' => 'web',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials',
            ]);

        $this->assertDatabaseHas('auth_attempts', [
            'channel_type' => 'web',
            'status' => 'failed',
            'user_id' => null,
        ]);
    }

    public function test_telegram_authentication_creates_user_and_succeeds(): void
    {
        $telegramData = [
            'telegram_id' => 123456789,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'chat_id' => 123456789,
        ];

        $response = $this->postJson('/api/auth', $telegramData, [
            'X-Auth-Channel-Type' => 'telegram',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user' => ['id', 'name', 'email'],
                'token',
                'attempt_id'
            ]);

        $this->assertDatabaseHas('users', [
            'telegram_id' => 123456789,
            'name' => 'John Doe',
            'registration_channel' => 'telegram',
        ]);

        $this->assertDatabaseHas('auth_attempts', [
            'channel_type' => 'telegram',
            'status' => 'success',
        ]);
    }

    public function test_n8n_webhook_authentication_requires_valid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/n8n/auth', [
            'token' => 'test-token',
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Должен вернуть ошибку из-за отсутствия валидной подписи
        $response->assertStatus(403); // Unauthorized из-за middleware
    }

    public function test_channel_detection_middleware_sets_headers(): void
    {
        $response = $this->postJson('/api/auth', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Проверяем что middleware установил заголовки
        $response->assertStatus(400); // Ошибка валидации, но middleware должен был отработать

        $this->assertDatabaseHas('auth_attempts', [
            'channel_type' => 'web', // По умолчанию web
        ]);
    }

    public function test_auth_attempt_status_check(): void
    {
        $attempt = AuthAttempt::create([
            'channel' => 'test_channel',
            'channel_type' => 'web',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'auth_token' => 'test-token',
            'expires_at' => now()->addMinutes(30),
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/auth/status/{$attempt->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'pending',
                'channel_type' => 'web',
                'expires_at' => $attempt->expires_at->toISOString(),
            ]);
    }

    public function test_rate_limiting_on_auth_endpoints(): void
    {
        // Создаем много запросов для проверки rate limiting
        for ($i = 0; $i < 6; $i++) { // 6 запросов, лимит 5 в минуту
            $response = $this->postJson('/api/auth', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ], [
                'X-Auth-Channel-Type' => 'web',
            ]);

            if ($i < 5) {
                $response->assertStatus(400); // Ошибка валидации, но не rate limit
            } else {
                // 6-й запрос должен быть заблокирован rate limiter
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }

    public function test_auth_attempt_expiration(): void
    {
        $attempt = AuthAttempt::create([
            'channel' => 'test_channel',
            'channel_type' => 'web',
            'ip_address' => '127.0.0.1',
            'auth_token' => 'test-token',
            'expires_at' => now()->subMinutes(1), // Уже истек
            'status' => 'pending',
        ]);

        $this->assertTrue($attempt->isExpired());
    }

    public function test_user_registration_metadata_storage(): void
    {
        $telegramData = [
            'telegram_id' => 987654321,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'username' => 'janesmith',
            'chat_id' => 987654321,
            'language_code' => 'ru',
        ];

        $this->postJson('/api/auth', $telegramData, [
            'X-Auth-Channel-Type' => 'telegram',
        ]);

        $this->assertDatabaseHas('users', [
            'telegram_id' => 987654321,
            'registration_channel' => 'telegram',
        ]);

        $user = User::where('telegram_id', 987654321)->first();
        $metadata = $user->getRegistrationMetadata();

        $this->assertEquals('janesmith', $metadata['username']);
        $this->assertEquals(987654321, $metadata['chat_id']);
        $this->assertEquals('ru', $metadata['language_code']);
    }
}