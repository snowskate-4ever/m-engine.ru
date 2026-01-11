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
        'app_id' => env('VK_APP_ID', '54418904'),
        'redirect_url' => env('VK_REDIRECT_URL', 'https://m-engine.ru'),
        'tunnel_url' => env('VK_TUNNEL_URL', null), // URL туннелинг сервиса (например, https://m-engine.loca.lt)
    ],

];
