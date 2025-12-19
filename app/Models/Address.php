<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'country_id',
        'region_id',
        'city_id',
        'street',
        'house',
        'building',
        'apartment',
        'floor',
        'entrance',
        'postal_code',
        'latitude',
        'longitude',
        'additional_info',
        'landmark',
        'address_type',
        'is_primary',
        'is_active',
        'is_verified',
        'is_public',
        'name',
        'description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Типы адресов
     */
    public const TYPES = [
        'home' => 'Домашний',
        'work' => 'Рабочий',
        'shipping' => 'Для доставки',
        'billing' => 'Для оплаты',
        'legal' => 'Юридический',
        'actual' => 'Фактический',
        'warehouse' => 'Склад',
        'shop' => 'Магазин',
        'office' => 'Офис',
        'other' => 'Другой',
    ];

    /**
     * Получить связанную сущность
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Страна
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Регион
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Город
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * События модели
     */
    protected static function boot()
    {
        parent::boot();

        // При создании основного адреса снимаем флаг is_primary у других адресов этой сущности
        static::creating(function ($address) {
            if ($address->is_primary) {
                self::where('addressable_id', $address->addressable_id)
                    ->where('addressable_type', $address->addressable_type)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });

        // При обновлении основного адреса
        static::updating(function ($address) {
            if ($address->is_primary && $address->isDirty('is_primary')) {
                self::where('addressable_id', $address->addressable_id)
                    ->where('addressable_type', $address->addressable_type)
                    ->where('id', '!=', $address->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Получить полный адрес в виде строки
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];
        
        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }
        
        if ($this->country) {
            $parts[] = $this->country->name;
        }
        
        if ($this->region) {
            $parts[] = $this->region->name;
        }
        
        if ($this->city) {
            $parts[] = $this->city->name;
        }
        
        if ($this->street) {
            $parts[] = "ул. {$this->street}";
        }
        
        if ($this->house) {
            $house = "д. {$this->house}";
            
            if ($this->building) {
                $house .= " корп. {$this->building}";
            }
            
            if ($this->entrance) {
                $house .= " подъезд {$this->entrance}";
            }
            
            $parts[] = $house;
        }
        
        if ($this->apartment) {
            $apartment = "кв. {$this->apartment}";
            
            if ($this->floor) {
                $apartment .= " этаж {$this->floor}";
            }
            
            $parts[] = $apartment;
        }
        
        return implode(', ', $parts);
    }

    /**
     * Получить короткий адрес (город, улица, дом)
     */
    public function getShortAddressAttribute(): string
    {
        $parts = [];
        
        if ($this->city) {
            $parts[] = $this->city->name;
        }
        
        if ($this->street) {
            $parts[] = "ул. {$this->street}";
        }
        
        if ($this->house) {
            $parts[] = "д. {$this->house}";
        }
        
        return implode(', ', $parts);
    }

    /**
     * Проверить, есть ли координаты
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Получить координаты в формате для карт
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }
        
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    /**
     * Получить тип адреса в читаемом формате
     */
    public function getAddressTypeNameAttribute(): string
    {
        return self::TYPES[$this->address_type] ?? $this->address_type;
    }

    /**
     * Scope для активных адресов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для основных адресов
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope для адресов определенного типа
     */
    public function scopeType($query, $type)
    {
        return $query->where('address_type', $type);
    }

    /**
     * Scope для адресов в определенной стране
     */
    public function scopeInCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope для адресов в определенном городе
     */
    public function scopeInCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope для поиска по адресным данным
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('street', 'LIKE', "%{$search}%")
              ->orWhere('house', 'LIKE', "%{$search}%")
              ->orWhere('building', 'LIKE', "%{$search}%")
              ->orWhere('apartment', 'LIKE', "%{$search}%")
              ->orWhere('postal_code', 'LIKE', "%{$search}%")
              ->orWhere('name', 'LIKE', "%{$search}%")
              ->orWhereHas('city', function ($cityQuery) use ($search) {
                  $cityQuery->where('name', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('country', function ($countryQuery) use ($search) {
                  $countryQuery->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    /**
     * Scope для адресов в радиусе от точки
     */
    public function scopeNear($query, $latitude, $longitude, $radius = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                     * cos(radians(latitude)) 
                     * cos(radians(longitude) - radians($longitude)) 
                     + sin(radians($latitude)) 
                     * sin(radians(latitude))))";

        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('*')
            ->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} < ?", [$radius])
            ->orderBy('distance');
    }

    /**
     * Установить адрес как основной
     */
    public function setAsPrimary(): bool
    {
        return $this->update(['is_primary' => true]);
    }

    /**
     * Проверить, является ли адрес проверенным
     */
    public function markAsVerified(): bool
    {
        return $this->update(['is_verified' => true]);
    }

    /**
     * Обновить координаты адреса
     */
    public function updateCoordinates(float $latitude, float $longitude): bool
    {
        return $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /**
     * Получить адреса для определенной сущности
     */
    public static function getForEntity($model, $type = null)
    {
        $query = $model->addresses()->active();
        
        if ($type) {
            $query->where('address_type', $type);
        }
        
        return $query->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить основной адрес сущности
     */
    public static function getPrimaryForEntity($model)
    {
        return $model->addresses()
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();
    }
}
