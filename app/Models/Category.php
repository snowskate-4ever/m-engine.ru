<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
        'parent_id',
    ];

    public function goods(): BelongsToMany
    {
        return $this->belongsToMany(Good::class, 'goods_to_categories')
            ->withTimestamps();
    }
}
