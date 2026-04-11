<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MusicProfileMembership extends Model
{
    protected $fillable = [
        'member_user_id',
        'entity_type',
        'entity_id',
        'role',
        'status',
        'invited_by_user_id',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => MusicMembershipRole::class,
            'status' => MusicMembershipStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity');
    }
}
