<?php

namespace App\Models;

use App\Models\Concerns\HasPublicPageLayouts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Musician extends Model
{
    use HasPublicPageLayouts;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'active',
        'photo',
        'avatar',
        'bio',
        'birth_date',
        'gender',
        'education',
        'availability',
        'available_for_booking',
        'is_session',
        'notes',
        'metadata',
        'slug',
        'public_page_enabled',
        'layout_draft',
        'layout_published',
    ];

    protected $casts = [
        'active' => 'boolean',
        'available_for_booking' => 'boolean',
        'is_session' => 'boolean',
        'public_page_enabled' => 'boolean',
        'availability' => 'array',
        'metadata' => 'array',
        'layout_draft' => 'array',
        'layout_published' => 'array',
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

    /**
     * @return BelongsToMany<Peformer, Musician>
     */
    public function peformers(): BelongsToMany
    {
        return $this->belongsToMany(Peformer::class, 'peformer_musician')
            ->using(PeformerMusician::class)
            ->withPivot([
                'status',
                'show_on_musician_profile',
                'invited_by_user_id',
                'responded_at',
            ])
            ->withTimestamps();
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
