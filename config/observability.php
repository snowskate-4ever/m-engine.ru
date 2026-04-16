<?php

declare(strict_types=1);

return [

    /*
    | Log and record a product metric when an API request exceeds this duration (ms).
    | 0 = disabled.
    */
    'slow_api_request_ms' => (int) env('OBSERVABILITY_SLOW_API_REQUEST_MS', 0),

    /*
    | Persist product_metric_events when no notification channels are resolved (rare).
    | Disabled in phpunit by default to avoid noise with Notification::fake().
    */
    'record_notification_gateway_metrics' => filter_var(
        env('OBSERVABILITY_RECORD_NOTIFICATION_GATEWAY', true),
        FILTER_VALIDATE_BOOLEAN,
    ),

];
