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

    // Scope для поиска (нечувствителен к регистру)
    public function scopeSearch(Builder $query, $term = null)
    {
        if (empty($term) || !is_string($term)) {
            return $query;
        }
        
        $searchTerm = trim($term);
        if (empty($searchTerm)) {
            return $query;
        }
        
        // Приводим поисковый терм к нижнему регистру в PHP для корректной работы с UTF-8
        $searchTermLower = mb_strtolower($searchTerm, 'UTF-8');
        
        $driver = $query->getConnection()->getDriverName();
        
        // Для PostgreSQL используем ILIKE (нечувствителен к регистру)
        if ($driver === 'pgsql') {
            return $query->where(function ($q) use ($searchTermLower) {
                $q->where('name', 'ILIKE', "%{$searchTermLower}%")
                  ->orWhere('description', 'ILIKE', "%{$searchTermLower}%");
            });
        }
        
        // Для SQLite используем фильтрацию в PHP для корректной работы с UTF-8
        // SQLite не поддерживает правильную работу LOWER() с UTF-8
        if ($driver === 'sqlite') {
            // Получаем все ID записей, которые соответствуют поиску
            $allResources = static::select('id', 'name', 'description')->get();
            $matchingIds = $allResources->filter(function ($resource) use ($searchTermLower) {
                $nameLower = mb_strtolower($resource->name ?? '', 'UTF-8');
                $descriptionLower = mb_strtolower($resource->description ?? '', 'UTF-8');
                return mb_stripos($nameLower, $searchTermLower) !== false 
                    || mb_stripos($descriptionLower, $searchTermLower) !== false;
            })->pluck('id')->toArray();
            
            if (empty($matchingIds)) {
                // Возвращаем запрос, который ничего не найдет
                return $query->whereRaw('1 = 0');
            }
            
            return $query->whereIn('id', $matchingIds);
        }
        
        // Для MySQL/MariaDB используем LOWER() для нечувствительности к регистру
        return $query->where(function ($q) use ($searchTermLower) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTermLower}%"])
              ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTermLower}%"]);
        });
    }
}
