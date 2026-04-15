<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'abilities',
        'rate_limit_per_minute',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashPlainToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public function verify(string $plain): bool
    {
        return hash_equals($this->token_hash, self::hashPlainToken($plain));
    }

    /**
     * @param  list<string>|null  $abilities
     * @return array{token: IntegrationApiToken, plain: string}
     */
    public static function mint(User $user, string $name, ?array $abilities = null, ?int $rateLimit = null): array
    {
        $plain = (string) config('integration.token_prefix', 'meng_').bin2hex(random_bytes(24));
        $token = self::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => self::hashPlainToken($plain),
            'abilities' => $abilities,
            'rate_limit_per_minute' => $rateLimit ?? (int) config('integration.default_rate_limit_per_minute', 120),
        ]);

        return ['token' => $token, 'plain' => $plain];
    }
}
