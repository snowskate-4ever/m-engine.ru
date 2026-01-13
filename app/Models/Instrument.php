<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instrument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Музыканты, играющие на этом инструменте
     */
    public function musicians(): BelongsToMany
    {
        return $this->belongsToMany(Musician::class, 'musician_instrument')
            ->withPivot('proficiency_level', 'is_primary')
            ->withTimestamps();
    }
}
