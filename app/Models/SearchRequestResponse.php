<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchRequestResponse extends Model
{
    protected $fillable = [
        'search_request_id',
        'responder_user_id',
        'message',
        'status',
        'contact_unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'contact_unlocked_at' => 'datetime',
        ];
    }

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_user_id');
    }
}
