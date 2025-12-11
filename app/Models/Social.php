<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Resource;

class Social extends Model
{
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
