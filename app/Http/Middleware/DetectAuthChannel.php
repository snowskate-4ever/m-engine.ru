<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectAuthChannel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Определяем канал по различным признакам
        $channel = $this->detectChannel($request);
        $channelType = $this->detectChannelType($request);

        $request->merge([
            'detected_channel' => $channel,
            'detected_channel_type' => $channelType
        ]);

        // Устанавливаем заголовки если они не указаны
        if (!$request->hasHeader('X-Auth-Channel')) {
            $request->headers->set('X-Auth-Channel', $channel);
        }

        if (!$request->hasHeader('X-Auth-Channel-Type')) {
            $request->headers->set('X-Auth-Channel-Type', $channelType);
        }

        return $next($request);
    }

    private function detectChannel(Request $request): string
    {
        // Проверка заголовков
        if ($request->header('X-Telegram-Bot-Token')) {
            return 'telegram_bot_' . substr($request->header('X-Telegram-Bot-Token'), -8);
        }

        if ($request->header('X-N8N-Webhook')) {
            return 'n8n_' . ($request->header('X-Workflow-Id') ?? 'unknown');
        }

        if ($request->header('X-API-Key')) {
            return 'api_' . substr($request->header('X-API-Key'), -8);
        }

        // Проверка User-Agent
        $userAgent = strtolower($request->userAgent() ?? '');

        if (str_contains($userAgent, 'telegram')) {
            return 'telegram_web_app';
        }

        if (str_contains($userAgent, 'postman') || str_contains($userAgent, 'insomnia')) {
            return 'api_testing_tool';
        }

        // Проверка referrer
        $referrer = $request->header('Referer', '');
        if (str_contains($referrer, 'n8n')) {
            return 'n8n_integration';
        }

        return 'direct';
    }

    private function detectChannelType(Request $request): string
    {
        // Явное указание в заголовках
        if ($request->header('X-Auth-Channel-Type')) {
            return $request->header('X-Auth-Channel-Type');
        }

        // Проверка по сигнатурам
        if ($request->header('X-N8N-Signature')) {
            return 'n8n_webhook';
        }

        if ($request->header('X-Telegram-Bot-Token') || str_contains(strtolower($request->userAgent() ?? ''), 'telegram')) {
            return 'telegram';
        }

        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            // Дополнительная проверка для API
            if ($request->header('X-API-Key') || $request->header('Authorization', '') !== 'Bearer ') {
                return 'api';
            }
        }

        return 'web';
    }
}
