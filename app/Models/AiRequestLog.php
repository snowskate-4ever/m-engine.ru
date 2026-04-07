<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiRequestSource;
use App\Enums\AiRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequestLog extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'source',
        'ai_server_model_id',
        'user_ai_connection_id',
        'duration_ms',
        'status',
        'http_status',
        'provider_error_code',
        'error_message',
        'tokens_prompt',
        'tokens_completion',
        'estimated_internal_cost',
        'provider_request_id',
        'prompt_excerpt',
        'response_excerpt',
        'prompt_full',
        'response_full',
    ];

    protected function casts(): array
    {
        return [
            'source' => AiRequestSource::class,
            'status' => AiRequestStatus::class,
            'duration_ms' => 'integer',
            'http_status' => 'integer',
            'tokens_prompt' => 'integer',
            'tokens_completion' => 'integer',
            'estimated_internal_cost' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
