<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $fillable = [
        'author_user_id',
        'reviewable_type',
        'reviewable_id',
        'contextable_type',
        'contextable_id',
        'rating',
        'body',
        'moderation_status',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function contextable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reply(): HasOne
    {
        return $this->hasOne(ReviewReply::class);
    }
}
