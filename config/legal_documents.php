<?php

declare(strict_types=1);

return [
    'moderator_user_ids' => array_filter(array_map(
        static fn (string $v): int => (int) trim($v),
        explode(',', (string) env('LEGAL_DOCUMENT_MODERATOR_USER_IDS', '')),
    )),
    'moderator_emails' => array_filter(array_map(
        static fn (string $v): string => trim($v),
        explode(',', (string) env('LEGAL_DOCUMENT_MODERATOR_EMAILS', '')),
    )),
];
