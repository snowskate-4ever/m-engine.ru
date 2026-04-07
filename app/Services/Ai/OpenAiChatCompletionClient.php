<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

final class OpenAiChatCompletionClient
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  null|list<array<string, mixed>>  $tools
     * @return array{
     *     ok: bool,
     *     duration_ms: int,
     *     content?: string|null,
     *     tool_calls?: list<array<string, mixed>>,
     *     prompt_tokens?: int,
     *     completion_tokens?: int,
     *     http_status?: int,
     *     error_message?: string,
     *     provider_error_code?: string,
     * }
     */
    public function chat(
        string $baseUrl,
        string $apiKey,
        string $model,
        array $messages,
        int $timeoutSeconds,
        ?array $tools = null,
    ): array {
        $url = rtrim($baseUrl, '/').'/chat/completions';
        $started = microtime(true);

        $body = [
            'model' => $model,
            'messages' => $messages,
            'parallel_tool_calls' => false,
        ];
        if ($tools !== null && $tools !== []) {
            $body['tools'] = $tools;
            $body['tool_choice'] = 'auto';
        }

        $response = Http::timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $status = $response->status();

        if (! $response->successful()) {
            $bodyJson = $response->json();
            $err = \is_array($bodyJson) ? ($bodyJson['error']['message'] ?? $bodyJson['error']['code'] ?? $response->body()) : $response->body();
            $code = \is_array($bodyJson) && isset($bodyJson['error']['code']) ? (string) $bodyJson['error']['code'] : null;

            return [
                'ok' => false,
                'duration_ms' => $durationMs,
                'http_status' => $status,
                'error_message' => is_string($err) ? $err : $response->body(),
                'provider_error_code' => $code,
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return [
                'ok' => false,
                'duration_ms' => $durationMs,
                'http_status' => $status,
                'error_message' => 'Invalid response shape from provider.',
            ];
        }

        $choice = $json['choices'][0] ?? null;
        if (! is_array($choice)) {
            return [
                'ok' => false,
                'duration_ms' => $durationMs,
                'http_status' => $status,
                'error_message' => 'Missing choices[0].',
            ];
        }

        $message = $choice['message'] ?? null;
        if (! is_array($message)) {
            return [
                'ok' => false,
                'duration_ms' => $durationMs,
                'http_status' => $status,
                'error_message' => 'Missing message object.',
            ];
        }

        $usage = $json['usage'] ?? [];
        $content = $message['content'] ?? null;
        if (! is_string($content) && $content !== null) {
            $content = null;
        }

        $toolCalls = $message['tool_calls'] ?? null;
        if ($toolCalls !== null && ! is_array($toolCalls)) {
            $toolCalls = null;
        }

        if (($toolCalls === null || $toolCalls === []) && ($content === null || $content === '')) {
            return [
                'ok' => false,
                'duration_ms' => $durationMs,
                'http_status' => $status,
                'error_message' => 'Empty assistant message.',
            ];
        }

        $out = [
            'ok' => true,
            'duration_ms' => $durationMs,
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'http_status' => $status,
        ];

        if ($content !== null) {
            $out['content'] = $content;
        }

        if ($toolCalls !== null && $toolCalls !== []) {
            $out['tool_calls'] = $toolCalls;
        }

        return $out;
    }
}
