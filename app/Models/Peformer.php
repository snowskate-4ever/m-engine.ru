<?php

namespace App\Models;

use App\Enums\PerformerKind;
use App\Models\Concerns\HasPublicPageLayouts;
use App\Models\Concerns\ModeratablePublicProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Peformer extends Model
{
    use HasPublicPageLayouts;
    use ModeratablePublicProfile;

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
        'slug',
        'public_page_enabled',
        'performer_kind',
        'layout_draft',
        'layout_published',
        'moderation_hidden_at',
        'moderation_reason',
        'moderation_review_requested_at',
    ];

    protected $casts = [
        'public_page_enabled' => 'boolean',
        'layout_draft' => 'array',
        'layout_published' => 'array',
        'performer_kind' => PerformerKind::class,
        'moderation_hidden_at' => 'datetime',
        'moderation_review_requested_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsToMany<Musician, Peformer>
     */
    public function musicians(): BelongsToMany
    {
        return $this->belongsToMany(Musician::class, 'peformer_musician')
            ->using(PeformerMusician::class)
            ->withPivot([
                'status',
                'show_on_musician_profile',
                'invited_by_user_id',
                'responded_at',
            ])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, Peformer>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'peformer_admins', 'peformer_id', 'user_id')
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
