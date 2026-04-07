<?php

declare(strict_types=1);

namespace App\Support;

final class SecretMask
{
    public static function tail(?string $secret, int $visible = 4): ?string
    {
        if ($secret === null || $secret === '') {
            return null;
        }

        $len = mb_strlen($secret);
        if ($len <= $visible) {
            return str_repeat('*', max(1, $len));
        }

        return '***'.mb_substr($secret, -$visible);
    }

    /**
     * @param  array<string, mixed>|null  $credentials
     */
    public static function hintFromCredentials(?array $credentials): ?string
    {
        if ($credentials === null || $credentials === []) {
            return null;
        }

        foreach (['api_key', 'openai_api_key', 'token', 'secret', 'access_token'] as $key) {
            $v = $credentials[$key] ?? null;
            if (is_string($v) && $v !== '') {
                return self::tail($v);
            }
        }

        foreach ($credentials as $v) {
            if (is_string($v) && $v !== '' && mb_strlen($v) >= 8) {
                return self::tail($v);
            }
        }

        return '***';
    }
}
