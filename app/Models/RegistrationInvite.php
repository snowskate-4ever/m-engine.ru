<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationInvite extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'token_hash',
        'token_encrypted',
        'is_active',
        'used_at',
        'used_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'used_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }
}
