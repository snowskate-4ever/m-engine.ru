<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MatchingInviteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerStudioInvite extends Model
{
    protected $fillable = [
        'organizer_user_id',
        'studio_id',
        'event_id',
        'search_request_id',
        'invited_by_user_id',
        'proposed_start_at',
        'proposed_end_at',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MatchingInviteStatus::class,
            'proposed_start_at' => 'datetime',
            'proposed_end_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }
}
