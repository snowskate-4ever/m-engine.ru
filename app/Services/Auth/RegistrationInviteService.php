<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\RegistrationInvite;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class RegistrationInviteService
{
    public function createForUser(User $user): RegistrationInvite
    {
        $token = $this->generateToken();

        return RegistrationInvite::query()->create([
            'created_by_user_id' => (int) $user->getKey(),
            'token_hash' => $this->hashToken($token),
            'token_encrypted' => Crypt::encryptString($token),
            'is_active' => true,
        ]);
    }

    public function findActiveByToken(?string $token): ?RegistrationInvite
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        return RegistrationInvite::query()
            ->where('token_hash', $this->hashToken($token))
            ->where('is_active', true)
            ->whereNull('used_at')
            ->first();
    }

    public function isActiveToken(?string $token): bool
    {
        return $this->findActiveByToken($token) !== null;
    }

    public function consumeToken(string $token, User $usedBy): bool
    {
        $hash = $this->hashToken($token);

        return RegistrationInvite::query()
            ->where('token_hash', $hash)
            ->where('is_active', true)
            ->whereNull('used_at')
            ->update([
                'is_active' => false,
                'used_at' => now(),
                'used_by_user_id' => (int) $usedBy->getKey(),
                'updated_at' => now(),
            ]) === 1;
    }

    public function registrationUrlForInvite(RegistrationInvite $invite): ?string
    {
        if (! $invite->is_active || $invite->used_at !== null) {
            return null;
        }

        try {
            $token = Crypt::decryptString($invite->token_encrypted);
        } catch (\Throwable) {
            return null;
        }

        return route('register', ['invite' => $token]);
    }

    private function generateToken(): string
    {
        return Str::random(64);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
