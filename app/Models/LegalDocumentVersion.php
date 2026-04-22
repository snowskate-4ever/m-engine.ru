<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocumentVersion extends Model
{
    protected $fillable = [
        'legal_document_id',
        'version',
        'payload_json',
        'file_path',
        'external_url',
        'checksum',
        'effective_from',
        'effective_to',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
