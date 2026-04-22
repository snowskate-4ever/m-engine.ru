<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Enums\LegalDocumentVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LegalDocument extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'document_type',
        'title',
        'status',
        'visibility',
        'current_version_id',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => LegalDocumentType::class,
            'status' => LegalDocumentStatus::class,
            'visibility' => LegalDocumentVisibility::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentVersion::class)->orderByDesc('version');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'current_version_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query
            ->where('status', LegalDocumentStatus::Approved->value)
            ->where('visibility', LegalDocumentVisibility::Public->value);
    }
}
