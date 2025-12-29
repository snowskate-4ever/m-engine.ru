<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Resource extends Model
{
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'active',
        'type_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_at' => 'date',
        'end_at' => 'date',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    // Scope для поиска
    public function scopeSearch(Builder $query, string $term = null)
    {
        if (!$term) {
            return $query;
        }
        
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%");
        });
    }
}
