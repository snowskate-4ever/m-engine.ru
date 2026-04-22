<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LegalEntityType;
use App\Enums\ModerationStatus;
use App\Models\Concerns\HasLegalDocuments;
use App\Models\Concerns\HasPublicPageLayouts;
use App\Models\Concerns\ModeratablePublicProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProducerCenter extends Model
{
    use HasPublicPageLayouts;
    use HasLegalDocuments;
    use ModeratablePublicProfile;

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
        'slug',
        'public_page_enabled',
        'layout_draft',
        'layout_published',
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
        'layout_draft' => 'array',
        'layout_published' => 'array',
        'legal_entity_type' => LegalEntityType::class,
        'moderation_hidden_at' => 'datetime',
        'moderation_review_requested_at' => 'datetime',
        'moderation_status' => ModerationStatus::class,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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
