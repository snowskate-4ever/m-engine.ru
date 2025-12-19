<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'name',
        'code',
        'type',
        'federal_district',
        'latitude',
        'longitude',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    // Scope для регионов определенной страны
    public function scopeForCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    // Scope для активных регионов
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
