<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthAttempt extends Model
{
    protected $fillable = [
        'channel',
        'channel_type',
        'user_id',
        'ip_address',
        'user_agent',
        'metadata',
        'status',
        'auth_token',
        'expires_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsSuccess(int $userId): void
    {
        $this->update([
            'status' => 'success',
            'user_id' => $userId,
            'expires_at' => null
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByChannelType($query, string $channelType)
    {
        return $query->where('channel_type', $channelType);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
