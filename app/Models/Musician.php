<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Musician extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'active',
        'photo',
        'avatar',
        'bio',
        'rating',
        'price_per_hour',
        'birth_date',
        'gender',
        'education',
        'availability',
        'available_for_booking',
        'is_session',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'active' => 'boolean',
        'available_for_booking' => 'boolean',
        'is_session' => 'boolean',
        'availability' => 'array',
        'metadata' => 'array',
        'rating' => 'decimal:2',
        'price_per_hour' => 'decimal:2',
        'birth_date' => 'date',
    ];

    /**
     * Связь с пользователем системы
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Инструменты, на которых играет музыкант
     */
    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'musician_instrument')
            ->withPivot('proficiency_level', 'is_primary')
            ->withTimestamps();
    }

    /**
     * Жанры музыки, в которых играет музыкант
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'musician_genre')
            ->withPivot('preference_level', 'is_primary')
            ->withTimestamps();
    }

    /**
     * Социальные сети и медиа-ссылки музыканта
     */
    public function socials(): MorphMany
    {
        return $this->morphMany(Social::class, 'socialable');
    }
}
