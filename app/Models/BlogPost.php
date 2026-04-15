<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlogPost extends Model
{
    protected $fillable = [
        'author_user_id',
        'owner_type',
        'owner_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'comments_enabled',
        'moderation_status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'comments_enabled' => 'boolean',
            'moderation_status' => ModerationStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function scopePublishedApproved(Builder $query): Builder
    {
        return $query
            ->where('moderation_status', ModerationStatus::Approved->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
