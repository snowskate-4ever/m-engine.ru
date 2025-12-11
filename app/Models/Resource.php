<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Type;

class Resource extends Model
{
    use Notifiable;

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }
}
