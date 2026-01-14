<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Resource extends Model
{
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
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

    /**
     * Социальные сети и медиа-ссылки ресурса
     */
    public function socials(): MorphMany
    {
        return $this->morphMany(Social::class, 'socialable');
    }

    /**
     * Static search method for compatibility with Laravel Scout-like API
     * 
     * @param string|null $search
     * @return Builder
     */
    public static function search(?string $search = null): Builder
    {
        $query = static::query();

        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            // Search by ID if search term is numeric
            if (is_numeric($search)) {
                $q->where('id', $search);
            }
            
            // Search by type name through relationship
            $q->orWhereHas('type', function ($typeQuery) use ($search) {
                $typeQuery->where('name', 'LIKE', "%{$search}%");
            });
        });
    }

    /**
     * Query scope for search functionality
     * 
     * @param Builder $query
     * @param string|null $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            // Search by ID if search term is numeric
            if (is_numeric($search)) {
                $q->where('id', $search);
            }
            
            // Search by type name through relationship
            $q->orWhereHas('type', function ($typeQuery) use ($search) {
                $typeQuery->where('name', 'LIKE', "%{$search}%");
            });
        });
    }
}
