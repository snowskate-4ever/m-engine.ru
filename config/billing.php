<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Квоты серверного ИИ (Europe/Moscow)
    |--------------------------------------------------------------------------
    |
    | BYOK не использует эти лимиты. Серверная модель (ai_server_model_id):
    | оплаченная подписка → без дневного потолка в этой версии;
    | иначе окно триала с trial_max_requests_per_day;
    | после окончания триала — unpaid_daily_server_request_allowance за сутки.
    |
    */
    'quota_timezone' => env('BILLING_AI_QUOTA_TIMEZONE', 'Europe/Moscow'),

    'trial_duration_days' => env('BILLING_AI_TRIAL_DAYS') !== null
        ? max(1, (int) env('BILLING_AI_TRIAL_DAYS'))
        : 14,

    'trial_max_requests_per_day' => env('BILLING_AI_TRIAL_MAX_REQUESTS_PER_DAY') !== null
        ? max(1, (int) env('BILLING_AI_TRIAL_MAX_REQUESTS_PER_DAY'))
        : 100,

    'unpaid_daily_server_request_allowance' => env('BILLING_AI_UNPAID_DAILY_ALLOWANCE') !== null
        ? max(0, (int) env('BILLING_AI_UNPAID_DAILY_ALLOWANCE'))
        : 20,

    /*
    |--------------------------------------------------------------------------
    | Webhook заглушки (HMAC SHA-256 тела запроса)
    |--------------------------------------------------------------------------
    |
    | POST /api/webhooks/billing/stub с заголовком X-Billing-Signature.
    | Пустой секрет — endpoint отключён (403).
    |
    */
    'webhook_secret' => env('BILLING_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Self-cap: верхняя граница добровольного лимита (если тариф без потолка)
    |--------------------------------------------------------------------------
    */
    'max_self_cap_requests_per_day' => env('BILLING_AI_MAX_SELF_CAP_PER_DAY') !== null
        ? max(1, (int) env('BILLING_AI_MAX_SELF_CAP_PER_DAY'))
        : 100_000,

    /*
    |--------------------------------------------------------------------------
    | Эквайринг (реализация PaymentGatewayContract)
    |--------------------------------------------------------------------------
    */
    'payment_gateway' => env('BILLING_PAYMENT_GATEWAY', 'stub'),

    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID', ''),
        'secret_key' => env('YOOKASSA_SECRET_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Идемпотентность webhook_event_id (stub) / payment id (ЮKassa), секунды
    |--------------------------------------------------------------------------
    */
    'webhook_event_idempotency_ttl_seconds' => env('BILLING_WEBHOOK_EVENT_TTL') !== null
        ? max(60, (int) env('BILLING_WEBHOOK_EVENT_TTL'))
        : 2_592_000,

];
