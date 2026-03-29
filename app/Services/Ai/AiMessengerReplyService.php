<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiRequestSource;
use App\Enums\AiRequestStatus;
use App\Enums\ConversationType;
use App\Enums\MessageKind;
use App\Events\Messenger\MessageSent;
use App\Models\AiServerModel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserAiChatSkill;
use App\Models\UserAiConnection;
use App\Support\AiChatDrivers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final class AiMessengerReplyService
{
    public function __construct(
        private readonly OpenAiChatCompletionClient $client,
        private readonly AiRequestLogWriter $logWriter,
        private readonly AiUsageLedgerWriter $ledgerWriter,
        private readonly AiServerQuotaService $serverQuota,
        private readonly AiChatToolLoop $toolLoop,
    ) {}

    public function replyToTriggerMessage(int $triggerMessageId): void
    {
        if (! config('ai.enabled')) {
            return;
        }

        $trigger = Message::query()->find($triggerMessageId);
        if ($trigger === null) {
            return;
        }

        $conversation = $trigger->conversation;
        if ($conversation->type !== ConversationType::Ai) {
            return;
        }

        if ($trigger->user_id === null || $trigger->is_forward || $trigger->kind !== MessageKind::Text) {
            return;
        }

        if ($trigger->attachments()->exists()) {
            return;
        }

        $human = User::query()->find($trigger->user_id);
        if ($human === null) {
            return;
        }

        if ($conversation->user_ai_connection_id !== null) {
            $perMin = (int) config('ai.byok.max_requests_per_minute', 0);
            if ($perMin > 0) {
                $key = 'byok-ai:'.$human->id;
                if (RateLimiter::tooManyAttempts($key, $perMin)) {
                    $this->createAssistantErrorMessage(
                        $conversation,
                        __('ui.messenger.ai_byok_rate_limited'),
                    );

                    return;
                }
            }
        }

        if ($conversation->ai_server_model_id !== null) {
            try {
                $this->serverQuota->assertMayConsumeServerTokensThisMonth($human);
                $this->serverQuota->assertMayConsumeServerAiRequest($human);
                $this->serverQuota->assertServerModelAllowedForPlan($human, $conversation->ai_server_model_id);
            } catch (AiServerQuotaDeniedException $e) {
                $message = match ($e->errorCode) {
                    'model_not_in_plan' => __('ui.messenger.ai_model_not_in_plan'),
                    'token_quota_exceeded' => __('ui.messenger.ai_token_quota_exceeded'),
                    default => __('ui.messenger.ai_quota_exceeded'),
                };
                $this->createAssistantErrorMessage($conversation, $message);

                return;
            }
        }

        $payload = $this->buildOpenAiPayload($conversation, $trigger);
        if ($payload === null) {
            $this->logWriter->write(
                $human,
                $conversation->ai_server_model_id !== null ? AiRequestSource::Server : AiRequestSource::Byok,
                AiRequestStatus::Error,
                0,
                0,
                0,
                $conversation->id,
                $conversation->ai_server_model_id,
                $conversation->user_ai_connection_id,
                null,
                'configuration',
                'Missing API key, inactive model, or unsupported driver.',
                null,
                null,
                null,
                null,
            );
            $this->createAssistantErrorMessage($conversation, 'Не настроен доступ к модели (ключ API или драйвер).');

            return;
        }

        $timeout = (int) config('ai.openai.timeout_seconds', 120);
        $serverModelId = $conversation->ai_server_model_id;
        $connId = $conversation->user_ai_connection_id;

        $messages = $payload['messages'];
        $toolDefs = $this->toolLoop->effectiveToolDefinitions($human, $conversation);
        $useToolLoop = config('ai.agent.tools_enabled', true) && $toolDefs !== [];

        if ($useToolLoop) {
            $messages = $this->appendAgentToolsSystemHint($messages);
            $loopResult = $this->toolLoop->run(
                $payload['base_url'],
                $payload['api_key'],
                $payload['model'],
                $messages,
                $timeout,
                $human,
                $conversation,
                $toolDefs,
            );
            $promptForLog = json_encode($loopResult['messages_snapshot'] ?? $messages, JSON_UNESCAPED_UNICODE) ?: null;

            if ($loopResult['ok']) {
                $estCost = $serverModelId !== null
                    ? $this->ledgerWriter->estimateServerInternalCost(
                        $serverModelId,
                        (int) ($loopResult['total_prompt_tokens'] ?? 0),
                        (int) ($loopResult['total_completion_tokens'] ?? 0),
                    )
                    : null;

                $this->logWriter->write(
                    $human,
                    $payload['source'],
                    AiRequestStatus::Success,
                    (int) ($loopResult['total_duration_ms'] ?? 0),
                    $loopResult['total_prompt_tokens'] ?? 0,
                    $loopResult['total_completion_tokens'] ?? 0,
                    $conversation->id,
                    $serverModelId,
                    $connId,
                    200,
                    null,
                    null,
                    null,
                    $estCost,
                    $promptForLog,
                    $loopResult['content'] ?? null,
                );

                DB::transaction(function () use ($conversation, $loopResult): void {
                    $assistant = Message::query()->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => null,
                        'kind' => MessageKind::Text,
                        'body' => $loopResult['content'] ?? '',
                        'is_forward' => false,
                    ]);
                    $conversation->touch();
                    $assistant->load(['user:id,name', 'attachments']);
                    DB::afterCommit(static function () use ($assistant): void {
                        broadcast(new MessageSent($assistant, true));
                    });
                });

                if ($conversation->ai_server_model_id !== null) {
                    $this->serverQuota->recordSuccessfulServerAiRequest($human);
                    $this->ledgerWriter->recordServerSuccess(
                        $human,
                        $serverModelId,
                        (int) ($loopResult['total_prompt_tokens'] ?? 0),
                        (int) ($loopResult['total_completion_tokens'] ?? 0),
                        $conversation->id,
                        $estCost,
                    );
                } elseif ($conversation->user_ai_connection_id !== null) {
                    $this->ledgerWriter->recordByokSuccess(
                        $human,
                        (int) ($loopResult['total_prompt_tokens'] ?? 0),
                        (int) ($loopResult['total_completion_tokens'] ?? 0),
                        $conversation->id,
                    );
                }

                return;
            }

            $this->logWriter->write(
                $human,
                $payload['source'],
                AiRequestStatus::Error,
                (int) ($loopResult['total_duration_ms'] ?? 0),
                $loopResult['total_prompt_tokens'] ?? 0,
                $loopResult['total_completion_tokens'] ?? 0,
                $conversation->id,
                $serverModelId,
                $connId,
                $loopResult['http_status'] ?? null,
                $loopResult['provider_error_code'] ?? null,
                $loopResult['error_message'] ?? 'Provider error',
                null,
                null,
                $promptForLog,
                null,
            );

            $this->createAssistantErrorMessage(
                $conversation,
                'Не удалось получить ответ модели. Попробуйте ещё раз позже.',
            );

            return;
        }

        $result = $this->client->chat(
            $payload['base_url'],
            $payload['api_key'],
            $payload['model'],
            $messages,
            $timeout,
            null,
        );

        $promptForLog = json_encode($messages, JSON_UNESCAPED_UNICODE) ?: null;

        if ($result['ok']) {
            $estCost = $serverModelId !== null
                ? $this->ledgerWriter->estimateServerInternalCost(
                    $serverModelId,
                    (int) ($result['prompt_tokens'] ?? 0),
                    (int) ($result['completion_tokens'] ?? 0),
                )
                : null;

            $this->logWriter->write(
                $human,
                $payload['source'],
                AiRequestStatus::Success,
                $result['duration_ms'],
                $result['prompt_tokens'] ?? 0,
                $result['completion_tokens'] ?? 0,
                $conversation->id,
                $serverModelId,
                $connId,
                $result['http_status'] ?? 200,
                null,
                null,
                null,
                $estCost,
                $promptForLog,
                $result['content'] ?? null,
            );

            DB::transaction(function () use ($conversation, $result): void {
                $assistant = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => null,
                    'kind' => MessageKind::Text,
                    'body' => $result['content'],
                    'is_forward' => false,
                ]);
                $conversation->touch();
                $assistant->load(['user:id,name', 'attachments']);
                DB::afterCommit(static function () use ($assistant): void {
                    broadcast(new MessageSent($assistant, true));
                });
            });

            if ($conversation->ai_server_model_id !== null) {
                $this->serverQuota->recordSuccessfulServerAiRequest($human);
                $this->ledgerWriter->recordServerSuccess(
                    $human,
                    $serverModelId,
                    (int) ($result['prompt_tokens'] ?? 0),
                    (int) ($result['completion_tokens'] ?? 0),
                    $conversation->id,
                    $estCost,
                );
            } elseif ($conversation->user_ai_connection_id !== null) {
                $this->ledgerWriter->recordByokSuccess(
                    $human,
                    (int) ($result['prompt_tokens'] ?? 0),
                    (int) ($result['completion_tokens'] ?? 0),
                    $conversation->id,
                );
            }

            return;
        }

        $this->logWriter->write(
            $human,
            $payload['source'],
            AiRequestStatus::Error,
            $result['duration_ms'],
            0,
            0,
            $conversation->id,
            $serverModelId,
            $connId,
            $result['http_status'] ?? null,
            $result['provider_error_code'] ?? null,
            $result['error_message'] ?? 'Provider error',
            null,
            null,
            $promptForLog,
            null,
        );

        $this->createAssistantErrorMessage(
            $conversation,
            'Не удалось получить ответ модели. Попробуйте ещё раз позже.',
        );
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    private function appendAgentToolsSystemHint(array $messages): array
    {
        if ($messages === []) {
            return $messages;
        }
        $first = $messages[0];
        if (($first['role'] ?? '') !== 'system') {
            return $messages;
        }
        $hint = "\n\n".trim((string) config('ai.agent.tools_system_hint'));
        $messages[0] = [
            ...$first,
            'content' => ($first['content'] ?? '').$hint,
        ];

        return $messages;
    }

    /**
     * @return null|array{
     *     source: AiRequestSource,
     *     api_key: string,
     *     base_url: string,
     *     model: string,
     *     messages: list<array{role: string, content: string}>
     * }
     */
    private function buildOpenAiPayload(Conversation $conversation, Message $trigger): ?array
    {
        $max = (int) config('ai.history_max_messages', 40);
        $messageIds = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', '<=', $trigger->id)
            ->orderByDesc('id')
            ->limit($max)
            ->pluck('id');

        $ordered = Message::query()
            ->whereIn('id', $messageIds)
            ->orderBy('id')
            ->get();

        $apiMessages = [];
        foreach ($ordered as $m) {
            if ($m->kind === MessageKind::File && ($m->body === null || $m->body === '')) {
                $apiMessages[] = ['role' => $m->user_id === null ? 'assistant' : 'user', 'content' => '[file]'];
            } elseif ($m->body !== null && $m->body !== '') {
                $role = $m->user_id === null ? 'assistant' : 'user';
                $apiMessages[] = ['role' => $role, 'content' => $m->body];
            }
        }

        $skillsText = UserAiChatSkill::query()
            ->where('conversation_id', $conversation->id)
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (UserAiChatSkill $s) => "## {$s->title}\n{$s->instruction_text}")
            ->implode("\n\n");

        $system = trim((string) config('ai.openai.system_prompt'));
        if ($skillsText !== '') {
            $system .= "\n\n--- User skill instructions ---\n".$skillsText;
        }

        array_unshift($apiMessages, ['role' => 'system', 'content' => $system]);

        if ($conversation->ai_server_model_id !== null) {
            $model = AiServerModel::query()
                ->whereKey($conversation->ai_server_model_id)
                ->where('is_active', true)
                ->whereHas('provider', static fn ($q) => $q->where('is_active', true))
                ->with('provider')
                ->first();
            if ($model === null || $model->provider === null) {
                return null;
            }
            if (! AiChatDrivers::isSupported($model->provider->driver)) {
                return null;
            }
            $cfg = $model->provider->config ?? [];
            $apiKey = is_array($cfg) ? ($cfg['api_key'] ?? null) : null;
            if (! is_string($apiKey) || $apiKey === '') {
                $apiKey = config('ai.openai.server_api_key');
            }
            if (! is_string($apiKey) || $apiKey === '') {
                return null;
            }
            $baseUrl = is_array($cfg) && isset($cfg['base_url']) && is_string($cfg['base_url']) && $cfg['base_url'] !== ''
                ? rtrim($cfg['base_url'], '/')
                : (string) config('ai.openai.base_url');

            return [
                'source' => AiRequestSource::Server,
                'api_key' => $apiKey,
                'base_url' => $baseUrl,
                'model' => $model->vendor_model_id,
                'messages' => $apiMessages,
            ];
        }

        if ($conversation->user_ai_connection_id !== null) {
            $conn = UserAiConnection::query()
                ->whereKey($conversation->user_ai_connection_id)
                ->where('enabled', true)
                ->first();
            if ($conn === null || ! AiChatDrivers::isSupported($conn->driver)) {
                return null;
            }
            $creds = $conn->credentials;
            if (! is_array($creds)) {
                return null;
            }
            $apiKey = $creds['api_key'] ?? $creds['openai_api_key'] ?? null;
            if (! is_string($apiKey) || $apiKey === '') {
                return null;
            }
            $baseUrl = isset($creds['base_url']) && is_string($creds['base_url']) && $creds['base_url'] !== ''
                ? rtrim($creds['base_url'], '/')
                : (string) config('ai.openai.base_url');
            $vendorModel = $creds['model'] ?? null;

            return [
                'source' => AiRequestSource::Byok,
                'api_key' => $apiKey,
                'base_url' => $baseUrl,
                'model' => is_string($vendorModel) && $vendorModel !== '' ? $vendorModel : 'gpt-4o-mini',
                'messages' => $apiMessages,
            ];
        }

        return null;
    }

    private function createAssistantErrorMessage(Conversation $conversation, string $body): void
    {
        DB::transaction(function () use ($conversation, $body): void {
            $m = Message::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => null,
                'kind' => MessageKind::System,
                'body' => $body,
                'is_forward' => false,
            ]);
            $conversation->touch();
            $m->load(['user:id,name', 'attachments']);
            DB::afterCommit(static function () use ($m): void {
                broadcast(new MessageSent($m, true));
            });
        });
    }
}
