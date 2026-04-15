<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdStatus;
use App\Enums\ModerationStatus;
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
        'ad_status',
        'moderation_status',
        'city_id',
        'target_kind',
        'my_city_only',
        'description',
        'published_at',
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
            'ad_status' => AdStatus::class,
            'moderation_status' => ModerationStatus::class,
            'criteria' => 'array',
            'my_city_only' => 'boolean',
            'published_at' => 'datetime',
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

    public function responses(): HasMany
    {
        return $this->hasMany(SearchRequestResponse::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
