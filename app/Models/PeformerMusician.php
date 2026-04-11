<?php

namespace App\Models;

use App\Enums\PerformerMembershipStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PeformerMusician extends Pivot
{
    protected $table = 'peformer_musician';

    protected $fillable = [
        'peformer_id',
        'musician_id',
        'status',
        'show_on_musician_profile',
        'invited_by_user_id',
        'search_request_id',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'show_on_musician_profile' => 'boolean',
            'responded_at' => 'datetime',
            'status' => PerformerMembershipStatus::class,
        ];
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }
}
