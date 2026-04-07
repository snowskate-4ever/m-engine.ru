<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiRequestSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLedger extends Model
{
    public $timestamps = false;

    protected $table = 'ai_usage_ledger';

    protected $fillable = [
        'user_id',
        'ai_server_model_id',
        'source',
        'tokens_prompt',
        'tokens_completion',
        'estimated_internal_cost',
        'conversation_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => AiRequestSource::class,
            'estimated_internal_cost' => 'decimal:6',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serverModel(): BelongsTo
    {
        return $this->belongsTo(AiServerModel::class, 'ai_server_model_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
