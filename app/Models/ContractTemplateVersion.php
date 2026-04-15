<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractTemplateVersion extends Model
{
    protected $fillable = [
        'contract_template_id',
        'version',
        'body_template',
        'variables_schema',
    ];

    protected function casts(): array
    {
        return [
            'variables_schema' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_template_version_id');
    }
}
