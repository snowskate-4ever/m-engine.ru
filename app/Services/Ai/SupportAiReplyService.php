<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiRequestSource;
use App\Enums\AiRequestStatus;
use App\Enums\MessageKind;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messenger\SupportChatService;

final class SupportAiReplyService
{
    public function __construct(
        private readonly OpenAiChatCompletionClient $client,
        private readonly AiRequestLogWriter $logWriter,
        private readonly SupportChatService $supportChats,
    ) {}

    public function generateDraft(Conversation $conversation, User $supportUser): ?string
    {
        if (! config('support_chat.ai.enabled')) {
            return null;
        }
        if (! $this->supportChats->isSupportConversation($conversation)) {
            return null;
        }

        $baseUrl = (string) config('ai.openai.base_url');
        $apiKey = (string) config('ai.openai.server_api_key');
        $model = (string) config('support_chat.ai.model', 'gpt-4o-mini');
        if ($apiKey === '' || $baseUrl === '' || $model === '') {
            return null;
        }

        $history = $this->buildHistory($conversation);
        $payload = [
            ['role' => 'system', 'content' => (string) config('support_chat.ai.system_prompt')],
            ...$history,
        ];
        $started = microtime(true);
        $result = $this->client->chat(
            $baseUrl,
            $apiKey,
            $model,
            $payload,
            (int) config('ai.openai.timeout_seconds', 120),
            null,
        );
        $durationMs = (int) ((microtime(true) - $started) * 1000);
        $promptForLog = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: null;

        if (! ($result['ok'] ?? false)) {
            $this->logWriter->write(
                $supportUser,
                AiRequestSource::Server,
                AiRequestStatus::Error,
                $durationMs,
                0,
                0,
                $conversation->id,
                null,
                null,
                $result['http_status'] ?? null,
                $result['provider_error_code'] ?? null,
                $result['error_message'] ?? 'Support AI provider error',
                null,
                null,
                $promptForLog,
                null,
            );

            return null;
        }

        $content = trim((string) ($result['content'] ?? ''));
        $maxChars = (int) config('support_chat.ai.max_response_chars', 4000);
        if ($maxChars > 0 && mb_strlen($content) > $maxChars) {
            $content = mb_substr($content, 0, $maxChars);
        }

        $this->logWriter->write(
            $supportUser,
            AiRequestSource::Server,
            AiRequestStatus::Success,
            $durationMs,
            (int) ($result['prompt_tokens'] ?? 0),
            (int) ($result['completion_tokens'] ?? 0),
            $conversation->id,
            null,
            null,
            $result['http_status'] ?? 200,
            null,
            null,
            null,
            null,
            $promptForLog,
            $content,
        );

        return $content === '' ? null : $content;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function buildHistory(Conversation $conversation): array
    {
        $max = (int) config('support_chat.ai.history_messages', 30);
        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->reverse()
            ->values();

        $rows = [];
        foreach ($messages as $message) {
            if ($message->kind === MessageKind::File) {
                continue;
            }
            $body = trim((string) ($message->body ?? ''));
            if ($body === '') {
                continue;
            }
            $rows[] = [
                'role' => $message->user_id === null ? 'assistant' : 'user',
                'content' => $body,
            ];
        }

        return $rows;
    }
}
