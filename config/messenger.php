<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | null = no limit (validate only by disk / PHP upload limits).
    |
    */
    'max_attachment_size_bytes' => env('MESSENGER_MAX_ATTACHMENT_BYTES') !== null
        ? (int) env('MESSENGER_MAX_ATTACHMENT_BYTES')
        : null,

    /**
     * @var list<string>|null Null or empty = any MIME allowed by storage.
     */
    'allowed_mime_types' => env('MESSENGER_ALLOWED_MIMES')
        ? array_map('trim', explode(',', (string) env('MESSENGER_ALLOWED_MIMES')))
        : null,

    'max_attachments_per_message' => env('MESSENGER_MAX_ATTACHMENTS_PER_MESSAGE') !== null
        ? (int) env('MESSENGER_MAX_ATTACHMENTS_PER_MESSAGE')
        : null,

    /*
    |--------------------------------------------------------------------------
    | Signed download URLs (GET without Bearer; signature + TTL)
    |--------------------------------------------------------------------------
    */
    'attachment_download_ttl_minutes' => env('MESSENGER_ATTACHMENT_DOWNLOAD_TTL') !== null
        ? max(1, (int) env('MESSENGER_ATTACHMENT_DOWNLOAD_TTL'))
        : 60,

    /*
    |--------------------------------------------------------------------------
    | Retention prune (scheduled command messenger:prune-retention)
    |--------------------------------------------------------------------------
    */
    'retention_prune_chunk_size' => env('MESSENGER_RETENTION_PRUNE_CHUNK') !== null
        ? max(1, (int) env('MESSENGER_RETENTION_PRUNE_CHUNK'))
        : 500,

    /*
    |--------------------------------------------------------------------------
    | Push (FCM legacy HTTP; optional — empty key = skip sends, job no-ops)
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'legacy_server_key' => env('FCM_LEGACY_SERVER_KEY'),
        'timeout_seconds' => env('FCM_HTTP_TIMEOUT') !== null
            ? max(1, (int) env('FCM_HTTP_TIMEOUT'))
            : 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Push — Apple APNs (HTTP/2 + JWT .p8; optional)
    |--------------------------------------------------------------------------
    |
    | When team_id, key_id, bundle_id and readable auth_key_path are set, iOS
    | tokens receive pushes via APNs. See .cursor/docs/messenger-push-apns.md
    |
    */
    'apns' => [
        'team_id' => env('APNS_TEAM_ID'),
        'key_id' => env('APNS_KEY_ID'),
        'auth_key_path' => env('APNS_AUTH_KEY_PATH'),
        'bundle_id' => env('APNS_BUNDLE_ID'),
        'use_sandbox' => filter_var(env('APNS_USE_SANDBOX', false), FILTER_VALIDATE_BOOL),
        'timeout_seconds' => env('APNS_HTTP_TIMEOUT') !== null
            ? max(1, (int) env('APNS_HTTP_TIMEOUT'))
            : 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Presence (foreground в открытом чате — не слать push этому устройству)
    |--------------------------------------------------------------------------
    */
    'presence_ttl_seconds' => env('MESSENGER_PRESENCE_TTL') !== null
        ? max(15, (int) env('MESSENGER_PRESENCE_TTL'))
        : 60,

];
