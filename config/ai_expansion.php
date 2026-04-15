<?php

declare(strict_types=1);

return [

    'auto_moderation_enabled' => (bool) env('AI_AUTO_MODERATION_ENABLED', false),

    'ad_copy_assist_enabled' => (bool) env('AI_AD_COPY_ASSIST_ENABLED', false),

    'recommender_enabled' => (bool) env('AI_RECOMMENDER_ENABLED', false),

    'review_anomaly_enabled' => (bool) env('AI_REVIEW_ANOMALY_ENABLED', false),

    'studio_forecast_enabled' => (bool) env('AI_STUDIO_FORECAST_ENABLED', false),

    'event_time_optimizer_enabled' => (bool) env('AI_EVENT_TIME_OPTIMIZER_ENABLED', false),

    'support_chatbot_enabled' => (bool) env('AI_SUPPORT_CHATBOT_ENABLED', false),

];
