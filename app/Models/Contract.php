<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contract extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING = 'awaiting_acceptance';

    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'contract_template_version_id',
        'party_a_type',
        'party_a_id',
        'party_b_type',
        'party_b_id',
        'rendered_body',
        'filled_variables',
        'status',
        'party_a_accepted_at',
        'party_b_accepted_at',
        'party_a_accepted_by_user_id',
        'party_b_accepted_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'filled_variables' => 'array',
            'party_a_accepted_at' => 'datetime',
            'party_b_accepted_at' => 'datetime',
        ];
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(ContractTemplateVersion::class, 'contract_template_version_id');
    }

    public function partyA(): MorphTo
    {
        return $this->morphTo('party_a');
    }

    public function partyB(): MorphTo
    {
        return $this->morphTo('party_b');
    }

    public function acceptanceAudits(): HasMany
    {
        return $this->hasMany(ContractAcceptanceAudit::class);
    }
}
