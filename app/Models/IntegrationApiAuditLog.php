<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationApiAuditLog extends Model
{
    protected $fillable = [
        'integration_api_token_id',
        'user_id',
        'method',
        'path',
        'status_code',
        'duration_ms',
        'ip',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(IntegrationApiToken::class, 'integration_api_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
