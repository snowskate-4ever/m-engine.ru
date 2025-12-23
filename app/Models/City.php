<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'region_id',
        'country_id',
        'name',
        'name_eng',
        'slug',
        'timezone',
        'latitude',
        'longitude',
        'population',
        'sort_order',
        'is_capital',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'population' => 'integer',
        'is_capital' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // Для связи Many-to-Many с регионами
    public function regions()
    {
        return $this->belongsToMany(Region::class, 'city_region')
            ->withPivot('is_main')
            ->withTimestamps();
    }

    // Scope для городов определенной страны
    public function scopeForCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    // Scope для городов определенного региона
    public function scopeForRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    // Scope для активных городов
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope для поиска по названию
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
            ->orWhere('name_eng', 'LIKE', "%{$search}%");
    }
}
