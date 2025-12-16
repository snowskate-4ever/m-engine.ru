<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'user_id',
        'updated_at',
        'created_at',
    ];

    // protected $casts = [
    //     'active' => 'boolean',
    //     'start_at' => 'datetime',
    //     'end_at' => 'datetime',
    // ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
