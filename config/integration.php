<?php

declare(strict_types=1);

return [

    'token_prefix' => env('INTEGRATION_TOKEN_PREFIX', 'meng_'),

    'default_rate_limit_per_minute' => (int) env('INTEGRATION_RATE_LIMIT', 120),

];
