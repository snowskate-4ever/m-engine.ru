<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch (roadmap §15)
    |--------------------------------------------------------------------------
    |
    | When false, AI HTTP entrypoints and jobs should no-op or return a stable
    | error payload without breaking the rest of the app.
    |
    */
    'enabled' => filter_var(env('AI_MASTER_ENABLED', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | AI request log (roadmap §2.13)
    |--------------------------------------------------------------------------
    */
    'request_log' => [
        'retention_days' => env('AI_REQUEST_LOG_RETENTION_DAYS') !== null
            ? max(1, (int) env('AI_REQUEST_LOG_RETENTION_DAYS'))
            : 30,

        'prompt_excerpt_max' => env('AI_PROMPT_EXCERPT_MAX') !== null
            ? max(0, (int) env('AI_PROMPT_EXCERPT_MAX'))
            : 1000,

        'response_excerpt_max' => env('AI_RESPONSE_EXCERPT_MAX') !== null
            ? max(0, (int) env('AI_RESPONSE_EXCERPT_MAX'))
            : 1000,

        /*
        | Полный промпт/ответ в БД — только при явном включении (152-ФЗ / внутренний аудит).
        | Обрезка по сроку короче, чем полное удаление строки журнала.
        */
        'store_full_prompt' => filter_var(env('AI_REQUEST_LOG_STORE_FULL_PROMPT', false), FILTER_VALIDATE_BOOL),
        'store_full_response' => filter_var(env('AI_REQUEST_LOG_STORE_FULL_RESPONSE', false), FILTER_VALIDATE_BOOL),

        'full_content_retention_days' => env('AI_REQUEST_LOG_FULL_CONTENT_RETENTION_DAYS') !== null
            ? max(1, (int) env('AI_REQUEST_LOG_FULL_CONTENT_RETENTION_DAYS'))
            : 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat skills (roadmap §2.14)
    |--------------------------------------------------------------------------
    */
    'max_skills_per_conversation' => env('AI_MAX_SKILLS_PER_CONVERSATION') !== null
        ? max(1, (int) env('AI_MAX_SKILLS_PER_CONVERSATION'))
        : 50,

    /*
    |--------------------------------------------------------------------------
    | Chat API drivers (OpenAI-compatible HTTP: POST …/chat/completions)
    |--------------------------------------------------------------------------
    |
    | Keys must match ai_providers.driver and BYOK user_ai_connections.driver.
    |
    */
    'chat_drivers' => [
        'openai' => 'OpenAI',
        'openai_compatible' => 'OpenAI-compatible (Groq, Mistral, local gateway, …)',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI-compatible defaults (used when provider config omits base_url)
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'base_url' => rtrim((string) env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'server_api_key' => env('AI_OPENAI_SERVER_API_KEY'),
        'timeout_seconds' => env('AI_OPENAI_TIMEOUT') !== null
            ? max(5, (int) env('AI_OPENAI_TIMEOUT'))
            : 120,
        'system_prompt' => (string) env(
            'AI_DEFAULT_SYSTEM_PROMPT',
            'You are a helpful assistant in a chat on the m-engine platform. Reply concisely in the same language as the user.',
        ),
    ],

    'history_max_messages' => env('AI_HISTORY_MAX_MESSAGES') !== null
        ? max(4, (int) env('AI_HISTORY_MAX_MESSAGES'))
        : 40,

    /*
    |--------------------------------------------------------------------------
    | Agent tools (roadmap §11 / §14) — OpenAI tool_calls in AI messenger chats
    |--------------------------------------------------------------------------
    */
    'agent' => [
        'tools_enabled' => filter_var(env('AI_AGENT_TOOLS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'max_tool_rounds' => env('AI_AGENT_MAX_TOOL_ROUNDS') !== null
            ? max(1, (int) env('AI_AGENT_MAX_TOOL_ROUNDS'))
            : 6,
        'tools_system_hint' => (string) env(
            'AI_AGENT_TOOLS_SYSTEM_HINT',
            'You can call tools to: schedule_reminder (one-time or recurring RRULE), create_task_with_deadline, link_event_booking_reminder. '
            .'Use them when the user asks to be reminded, to create a task with a deadline, or to get a reminder about their event. '
            .'Datetimes without timezone are Europe/Moscow.',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | BYOK — защита инфраструктуры (запросов в минуту на пользователя)
    |--------------------------------------------------------------------------
    |
    | 0 = не ограничивать. Счётчик по пользователю, скользящее окно 60 с.
    |
    */
    'byok' => [
        'max_requests_per_minute' => env('AI_BYOK_MAX_REQUESTS_PER_MINUTE') !== null
            ? max(0, (int) env('AI_BYOK_MAX_REQUESTS_PER_MINUTE'))
            : 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ads matching (admin-managed)
    |--------------------------------------------------------------------------
    */
    'matching' => [
        'enabled' => filter_var(env('AI_MATCHING_ENABLED', true), FILTER_VALIDATE_BOOL),
        'provider' => env('AI_MATCHING_PROVIDER', 'openai'),
        'model' => env('AI_MATCHING_MODEL', 'gpt-4o-mini'),
        'score_threshold' => env('AI_MATCHING_SCORE_THRESHOLD') !== null
            ? (float) env('AI_MATCHING_SCORE_THRESHOLD')
            : 0.65,
        'weights' => [
            'geo' => env('AI_MATCHING_WEIGHT_GEO') !== null ? (float) env('AI_MATCHING_WEIGHT_GEO') : 0.3,
            'genre' => env('AI_MATCHING_WEIGHT_GENRE') !== null ? (float) env('AI_MATCHING_WEIGHT_GENRE') : 0.25,
            'rating' => env('AI_MATCHING_WEIGHT_RATING') !== null ? (float) env('AI_MATCHING_WEIGHT_RATING') : 0.15,
            'activity' => env('AI_MATCHING_WEIGHT_ACTIVITY') !== null ? (float) env('AI_MATCHING_WEIGHT_ACTIVITY') : 0.1,
            'text' => env('AI_MATCHING_WEIGHT_TEXT') !== null ? (float) env('AI_MATCHING_WEIGHT_TEXT') : 0.2,
        ],
    ],

];
