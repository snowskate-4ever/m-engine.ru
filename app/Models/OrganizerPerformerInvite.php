<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MatchingInviteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerPerformerInvite extends Model
{
    protected $fillable = [
        'organizer_user_id',
        'peformer_id',
        'event_id',
        'search_request_id',
        'invited_by_user_id',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MatchingInviteStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    public function peformer(): BelongsTo
    {
        return $this->belongsTo(Peformer::class, 'peformer_id');
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
