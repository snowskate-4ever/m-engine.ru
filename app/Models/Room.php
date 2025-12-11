<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Type;
use App\Models\Resource;

class Room extends Model
{

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }
}
