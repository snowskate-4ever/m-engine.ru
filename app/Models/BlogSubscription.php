<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlogSubscription extends Model
{
    protected $fillable = [
        'subscriber_user_id',
        'owner_type',
        'owner_id',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_user_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
