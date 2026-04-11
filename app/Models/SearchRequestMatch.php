<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchRequestMatch extends Model
{
    protected $fillable = [
        'search_request_id',
        'candidate_type',
        'candidate_id',
        'score',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'score' => 'decimal:4',
        ];
    }

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }

    public function candidate(): MorphTo
    {
        return $this->morphTo();
    }
}
