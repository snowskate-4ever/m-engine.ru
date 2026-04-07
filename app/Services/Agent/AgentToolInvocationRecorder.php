<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\AgentToolInvocation;

final class AgentToolInvocationRecorder
{
    public function record(
        int $userId,
        ?int $conversationId,
        string $toolName,
        string $argumentsHash,
        bool $ok,
        ?string $errorMessage,
    ): void {
        AgentToolInvocation::query()->create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'tool_name' => mb_substr($toolName, 0, 64),
            'arguments_hash' => mb_substr($argumentsHash, 0, 64),
            'ok' => $ok,
            'error_message' => $errorMessage !== null && $errorMessage !== '' ? mb_substr($errorMessage, 0, 65535) : null,
            'created_at' => now(),
        ]);
    }
}
