<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Agent\AgentToolExecutor;
use App\Services\Agent\AgentToolRegistry;

final class AiChatToolLoop
{
    public function __construct(
        private readonly OpenAiChatCompletionClient $client,
        private readonly AgentToolRegistry $registry,
        private readonly AgentToolExecutor $executor,
        private readonly AiServerQuotaService $quota,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function effectiveToolDefinitions(User $user, Conversation $conversation): array
    {
        if (! config('ai.agent.tools_enabled', true)) {
            return [];
        }

        if ($conversation->ai_server_model_id !== null) {
            if (! $this->quota->serverTierToolsEnabled($user)) {
                return [];
            }
        }

        return $this->registry->definitions();
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $toolDefinitions
     * @return array{
     *     ok: bool,
     *     content?: string,
     *     total_prompt_tokens: int,
     *     total_completion_tokens: int,
     *     messages_snapshot?: list<array<string, mixed>>,
     *     error_message?: string,
     *     http_status?: int,
     *     provider_error_code?: string,
     * }
     */
    public function run(
        string $baseUrl,
        string $apiKey,
        string $model,
        array $messages,
        int $timeoutSeconds,
        User $user,
        Conversation $conversation,
        array $toolDefinitions,
    ): array {
        $tools = $toolDefinitions;
        $maxRounds = max(1, (int) config('ai.agent.max_tool_rounds', 6));
        $totalPt = 0;
        $totalCt = 0;
        $totalDurationMs = 0;

        for ($round = 0; $round < $maxRounds; $round++) {
            $result = $this->client->chat($baseUrl, $apiKey, $model, $messages, $timeoutSeconds, $tools === [] ? null : $tools);
            $totalDurationMs += (int) ($result['duration_ms'] ?? 0);
            $totalPt += (int) ($result['prompt_tokens'] ?? 0);
            $totalCt += (int) ($result['completion_tokens'] ?? 0);

            if (! $result['ok']) {
                return [
                    'ok' => false,
                    'total_duration_ms' => $totalDurationMs,
                    'total_prompt_tokens' => $totalPt,
                    'total_completion_tokens' => $totalCt,
                    'messages_snapshot' => $messages,
                    'error_message' => $result['error_message'] ?? 'Provider error',
                    'http_status' => $result['http_status'] ?? null,
                    'provider_error_code' => $result['provider_error_code'] ?? null,
                ];
            }

            $toolCalls = $result['tool_calls'] ?? null;
            if ($tools !== [] && is_array($toolCalls) && $toolCalls !== []) {
                $assistantRow = [
                    'role' => 'assistant',
                    'content' => $result['content'] ?? null,
                    'tool_calls' => $toolCalls,
                ];
                $messages[] = $assistantRow;

                foreach ($toolCalls as $tc) {
                    if (! is_array($tc)) {
                        continue;
                    }
                    $id = isset($tc['id']) && is_string($tc['id']) ? $tc['id'] : '';
                    $fn = isset($tc['function']) && is_array($tc['function']) ? $tc['function'] : null;
                    $name = $fn !== null && isset($fn['name']) && is_string($fn['name']) ? $fn['name'] : '';
                    $args = $fn !== null && isset($fn['arguments']) && is_string($fn['arguments']) ? $fn['arguments'] : '{}';
                    if ($id === '' || $name === '') {
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $id !== '' ? $id : 'unknown',
                            'content' => json_encode(['ok' => false, 'error' => 'Malformed tool_call'], JSON_UNESCAPED_UNICODE) ?: '{}',
                        ];

                        continue;
                    }
                    $out = $this->executor->execute($user, $conversation, $name, $args);
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $id,
                        'content' => $out,
                    ];
                }

                continue;
            }

            $content = $result['content'] ?? '';
            if (! is_string($content)) {
                $content = '';
            }

            return [
                'ok' => true,
                'content' => $content,
                'total_duration_ms' => $totalDurationMs,
                'total_prompt_tokens' => $totalPt,
                'total_completion_tokens' => $totalCt,
                'messages_snapshot' => $messages,
            ];
        }

        return [
            'ok' => false,
            'total_duration_ms' => $totalDurationMs,
            'total_prompt_tokens' => $totalPt,
            'total_completion_tokens' => $totalCt,
            'messages_snapshot' => $messages,
            'error_message' => 'Maximum tool rounds exceeded.',
        ];
    }
}
