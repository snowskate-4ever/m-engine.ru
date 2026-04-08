<?php

namespace App\Models;

use App\Enums\LegalEntityType;
use App\Models\Concerns\HasPublicPageLayouts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Rehersal extends Model
{
    use HasPublicPageLayouts;

    protected $table = 'rehearsals';

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
    ];

    protected $casts = [
        'public_page_enabled' => 'boolean',
        'layout_draft' => 'array',
        'layout_published' => 'array',
        'legal_entity_type' => LegalEntityType::class,
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
