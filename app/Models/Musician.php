<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use App\Models\Concerns\HasPublicPageLayouts;
use App\Models\Concerns\ModeratablePublicProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Musician extends Model
{
    use HasPublicPageLayouts;
    use ModeratablePublicProfile;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'active',
        'photo',
        'avatar',
        'experience_started_on',
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
        'moderation_hidden_at',
        'moderation_reason',
        'moderation_review_requested_at',
        'moderation_status',
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
        'experience_started_on' => 'date',
        'birth_date' => 'date',
        'moderation_hidden_at' => 'datetime',
        'moderation_review_requested_at' => 'datetime',
        'moderation_status' => ModerationStatus::class,
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
     * Города, в которых готов работать / выступать (кроме адресов из адресной книги).
     *
     * @return BelongsToMany<City, Musician>
     */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'musician_city')
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

    public function memberships(): MorphMany
    {
        return $this->morphMany(MusicProfileMembership::class, 'entity');
    }

    /**
     * Полных лет опыта от первого дня месяца начала до текущей даты (для публички и критериев).
     */
    protected function yearsOfExperience(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes): ?int {
                $raw = $attributes['experience_started_on'] ?? null;
                if ($raw === null || $raw === '') {
                    return null;
                }
                $start = CarbonImmutable::parse((string) $raw)->startOfMonth();

                return max(0, (int) $start->diffInYears(now()));
            }
        );
    }
}
