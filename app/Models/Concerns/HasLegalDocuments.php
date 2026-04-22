<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\LegalDocument;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLegalDocuments
{
    public function legalDocuments(): MorphMany
    {
        return $this->morphMany(LegalDocument::class, 'owner');
    }
}
