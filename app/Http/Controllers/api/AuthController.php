<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\N8NWebhookRequest;
use App\Models\AuthAttempt;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Универсальный endpoint для авторизации из любого канала
     */
    public function authenticate(AuthRequest $request): JsonResponse
    {
        $channel = $request->header('X-Auth-Channel', 'web');
        $channelType = $request->header('X-Auth-Channel-Type', 'web');

        try {
            $authAttempt = $this->authService->createAttempt(
                channel: $channel,
                channelType: $channelType,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: $request->validated()
            );

            return match ($channelType) {
                'web' => $this->handleWebAuth($request, $authAttempt),
                'telegram' => $this->handleTelegramAuth($request, $authAttempt),
                'api' => $this->handleApiAuth($request, $authAttempt),
                'n8n_webhook' => $this->handleN8NAuth($request, $authAttempt),
                default => response()->json([
                    'error' => 'Unsupported channel type',
                    'attempt_id' => $authAttempt->id
                ], 400)
            };
        } catch (\Exception $e) {
            Log::channel('auth')->error('Auth error', [
                'channel' => $channel,
                'channel_type' => $channelType,
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Authentication failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Обработка веб-авторизации
     */
    private function handleWebAuth(AuthRequest $request, AuthAttempt $authAttempt): JsonResponse
    {
        $data = $request->validated();

        // Попытка найти существующего пользователя
        $user = Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        if (!$user) {
            $authAttempt->markAsFailed();
            return response()->json([
                'error' => 'Invalid credentials',
                'attempt_id' => $authAttempt->id
            ], 401);
        }

        $user = Auth::user();
        $authAttempt->markAsSuccess($user->id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $this->authService->generateApiToken($user),
            'attempt_id' => $authAttempt->id
        ]);
    }

    /**
     * Обработка Telegram авторизации
     */
    private function handleTelegramAuth(AuthRequest $request, AuthAttempt $authAttempt): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->processTelegramAuth($data, $authAttempt);
        $authAttempt->markAsSuccess($user->id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $this->authService->generateApiToken($user),
            'attempt_id' => $authAttempt->id
        ]);
    }

    /**
     * Обработка API авторизации
     */
    private function handleApiAuth(AuthRequest $request, AuthAttempt $authAttempt): JsonResponse
    {
        $data = $request->validated();

        // Для API можно использовать стандартную логику или расширить
        $user = $this->authService->processWebAuth($data, $authAttempt);
        $authAttempt->markAsSuccess($user->id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $this->authService->generateApiToken($user),
            'attempt_id' => $authAttempt->id
        ]);
    }

    /**
     * Обработка N8N авторизации
     */
    private function handleN8NAuth(AuthRequest $request, AuthAttempt $authAttempt): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->processN8NAuth($data, $authAttempt);
        $authAttempt->markAsSuccess($user->id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $this->authService->generateApiToken($user),
            'attempt_id' => $authAttempt->id
        ]);
    }

    /**
     * Проверка статуса авторизационной попытки
     */
    public function checkStatus(string $attemptId): JsonResponse
    {
        $attempt = AuthAttempt::findOrFail($attemptId);

        return response()->json([
            'status' => $attempt->status,
            'user_id' => $attempt->user_id,
            'channel' => $attempt->channel,
            'channel_type' => $attempt->channel_type,
            'expires_at' => $attempt->expires_at,
            'created_at' => $attempt->created_at,
            'metadata' => $attempt->metadata
        ]);
    }

    /**
     * Webhook для N8N для подтверждения авторизации
     */
    public function n8nWebhook(N8NWebhookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attempt = $this->authService->findAttemptByToken($validated['token']);

        if (!$attempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        if (!$this->authService->validateAttempt($attempt)) {
            return response()->json(['error' => 'Attempt expired or invalid'], 410);
        }

        // Создание пользователя или привязка к существующему
        $user = $this->authService->processN8NAuth($validated, $attempt);
        $attempt->markAsSuccess($user->id);

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'auth_token' => $this->authService->generateApiToken($user),
            'attempt_id' => $attempt->id,
            'registration_channel' => $user->registration_channel
        ]);
    }
}