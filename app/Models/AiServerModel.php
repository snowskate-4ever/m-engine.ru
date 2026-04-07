<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiServerModel extends Model
{
    protected $fillable = [
        'ai_provider_id',
        'vendor_model_id',
        'display_name',
        'internal_cost_per_1k_prompt_tokens',
        'internal_cost_per_1k_completion_tokens',
        'estimated_cost_per_request',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'internal_cost_per_1k_prompt_tokens' => 'decimal:6',
            'internal_cost_per_1k_completion_tokens' => 'decimal:6',
            'estimated_cost_per_request' => 'decimal:6',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }
}
