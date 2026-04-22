<?php

namespace App\Models;

use App\Enums\LegalEntityType;
use App\Enums\ModerationStatus;
use App\Models\Concerns\HasLegalDocuments;
use App\Models\Concerns\HasPublicPageLayouts;
use App\Models\Concerns\ModeratablePublicProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Teacher extends Model
{
    use HasPublicPageLayouts;
    use HasLegalDocuments;
    use ModeratablePublicProfile;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'slug',
        'public_page_enabled',
        'layout_draft',
        'layout_published',
        'available_other_cities',
        'legal_entity_type',
        'company_name',
        'inn',
        'ogrn',
        'moderation_hidden_at',
        'moderation_reason',
        'moderation_review_requested_at',
        'moderation_status',
    ];

    protected $casts = [
        'public_page_enabled' => 'boolean',
        'available_other_cities' => 'boolean',
        'layout_draft' => 'array',
        'layout_published' => 'array',
        'legal_entity_type' => LegalEntityType::class,
        'moderation_hidden_at' => 'datetime',
        'moderation_review_requested_at' => 'datetime',
        'moderation_status' => ModerationStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<City, Teacher>
     */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'teacher_city')
            ->withTimestamps();
    }

    public function socials(): MorphMany
    {
        return $this->morphMany(Social::class, 'socialable');
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
