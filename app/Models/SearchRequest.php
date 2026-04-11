<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SearchGoal;
use App\Enums\SearchRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchRequest extends Model
{
    protected $fillable = [
        'search_goal',
        'status',
        'initiator_type',
        'initiator_id',
        'created_by_user_id',
        'criteria',
        'submitted_at',
        'expires_at',
        'fulfilled_at',
        'closure_reason',
        'fulfillment_context',
    ];

    protected function casts(): array
    {
        return [
            'search_goal' => SearchGoal::class,
            'status' => SearchRequestStatus::class,
            'criteria' => 'array',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'fulfillment_context' => 'array',
        ];
    }

    public function initiator(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(SearchRequestMatch::class);
    }
}
