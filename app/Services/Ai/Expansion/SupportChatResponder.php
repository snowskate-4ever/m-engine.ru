<?php

declare(strict_types=1);

namespace App\Services\Ai\Expansion;

final class SupportChatResponder
{
    public function reply(string $userMessage): string
    {
        if (! config('ai_expansion.support_chatbot_enabled')) {
            return 'Support chatbot is disabled. See docs/MOBILE_OFFLINE_SYNC.md and config/ai_expansion.php.';
        }

        $m = mb_strtolower($userMessage);
        if (str_contains($m, 'api')) {
            return 'Use Bearer integration tokens at /api/integration/v1/me after minting via POST /api/integration/tokens.';
        }

        return 'Thanks for your message. A human will follow up if needed.';
    }
}
