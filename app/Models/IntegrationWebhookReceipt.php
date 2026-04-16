<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationWebhookReceipt extends Model
{
    protected $fillable = [
        'idempotency_key',
        'event_name',
        'status',
        'error_message',
        'client_ip',
        'signature',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
