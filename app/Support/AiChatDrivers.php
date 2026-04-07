<?php

declare(strict_types=1);

namespace App\Support;

final class AiChatDrivers
{
    /**
     * @return array<string, string> driver => label
     */
    public static function labels(): array
    {
        return config('ai.chat_drivers', []);
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }

    public static function isSupported(string $driver): bool
    {
        return array_key_exists($driver, self::labels());
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForSelect(): array
    {
        return self::labels();
    }
}
