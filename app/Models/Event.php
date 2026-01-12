<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'active',
        'booking_resource_id',
        'booked_resource_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
    
    public function bookingResource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'booking_resource_id');
    }

    public function bookedResource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'booked_resource_id');
    }
}
