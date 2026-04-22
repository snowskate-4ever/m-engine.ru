<?php

declare(strict_types=1);

return [
    'support_user_email' => (string) env('SUPPORT_CHAT_USER_EMAIL', 'support@m-engine.ru'),
    'support_user_name' => (string) env('SUPPORT_CHAT_USER_NAME', 'Поддержка'),
    'auto_create_user' => filter_var(env('SUPPORT_CHAT_AUTO_CREATE_USER', true), FILTER_VALIDATE_BOOL),

    'operator_roles' => array_values(array_filter(array_map(
        static fn (string $v): string => trim($v),
        explode(',', (string) env('SUPPORT_CHAT_OPERATOR_ROLES', 'Admin,Manager')),
    ))),

    'ai' => [
        'enabled' => filter_var(env('SUPPORT_CHAT_AI_ENABLED', true), FILTER_VALIDATE_BOOL),
        'allow_auto_send' => filter_var(env('SUPPORT_CHAT_AI_ALLOW_AUTO_SEND', false), FILTER_VALIDATE_BOOL),
        'model' => (string) env('SUPPORT_CHAT_AI_MODEL', 'gpt-4o-mini'),
        'history_messages' => env('SUPPORT_CHAT_AI_HISTORY_MESSAGES') !== null
            ? max(4, (int) env('SUPPORT_CHAT_AI_HISTORY_MESSAGES'))
            : 30,
        'max_response_chars' => env('SUPPORT_CHAT_AI_MAX_RESPONSE_CHARS') !== null
            ? max(200, (int) env('SUPPORT_CHAT_AI_MAX_RESPONSE_CHARS'))
            : 4000,
        'system_prompt' => (string) env(
            'SUPPORT_CHAT_AI_SYSTEM_PROMPT',
            'You are a customer support assistant for m-engine. '
            .'Write concise and helpful replies in the same language as the user. '
            .'Do not invent policies, payments, or legal claims. '
            .'If data is missing, ask a clarifying question.',
        ),
    ],
];
