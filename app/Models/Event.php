<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Room;
use App\Models\Resource;

class Event extends Model
{
    
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
