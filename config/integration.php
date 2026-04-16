<?php

declare(strict_types=1);

return [

    'token_prefix' => env('INTEGRATION_TOKEN_PREFIX', 'meng_'),

    'default_rate_limit_per_minute' => (int) env('INTEGRATION_RATE_LIMIT', 120),

    'webhooks_rate_limit_per_minute' => (int) env('INTEGRATION_WEBHOOKS_RATE_LIMIT', 120),

    'allowed_abilities' => [
        '*',
        'me:read',
        'analytics:read',
        'analytics:export',
    ],

    'webhook_signature_secret' => (string) env('INTEGRATION_WEBHOOK_SIGNATURE_SECRET', ''),

];
