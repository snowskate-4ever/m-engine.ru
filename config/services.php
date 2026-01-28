<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'vk' => [
        'access_token' => env('VK_ACCESS_TOKEN'),
        'api_version' => env('VK_API_VERSION', '5.131'),
        'api_url' => env('VK_API_URL', 'https://api.vk.com/method'),
        'verify_ssl' => env('VK_VERIFY_SSL', true),
        'app_id' => env('VK_APP_ID', '51417426'),
        'protected_key' => env('VK_PROTECTED_KEY'),
        'client_secret' => env('VK_CLIENT_SECRET'),
        'redirect_url' => env('VK_REDIRECT_URL', 'https://m-engine.ru'),
        'tunnel_url' => env('VK_TUNNEL_URL', null), // URL туннелинг сервиса (например, https://m-engine.loca.lt)
    ],

    /*
    |--------------------------------------------------------------------------
    | N8N Integration
    |--------------------------------------------------------------------------
    */
    'n8n' => [
        'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
        'allowed_ips' => explode(',', env('N8N_ALLOWED_IPS', '')),
        'default_workflow_url' => env('N8N_WORKFLOW_URL'),
        'timeout' => env('N8N_TIMEOUT', 30),
        'retry_attempts' => env('N8N_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Integration
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'auth_timeout' => env('TELEGRAM_AUTH_TIMEOUT', 300),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org/bot'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Channels Configuration
    |--------------------------------------------------------------------------
    */
    'auth_channels' => [
        'default_expiry' => env('AUTH_DEFAULT_EXPIRY', 1800), // 30 минут
        'cleanup_days' => env('AUTH_CLEANUP_DAYS', 30),
        'rate_limit' => [
            'web' => env('AUTH_RATE_LIMIT_WEB', 5),        // попыток в минуту
            'api' => env('AUTH_RATE_LIMIT_API', 10),
            'telegram' => env('AUTH_RATE_LIMIT_TELEGRAM', 3),
            'n8n' => env('AUTH_RATE_LIMIT_N8N', 60),
        ],
        'max_attempts_per_hour' => env('AUTH_MAX_ATTEMPTS_PER_HOUR', 20),
        'block_duration_minutes' => env('AUTH_BLOCK_DURATION_MINUTES', 15),
    ],

];
