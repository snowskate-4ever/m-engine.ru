<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Social extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'socialable_id',
        'socialable_type',
        'link',
        'type',
        'name',
        'description',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Полиморфная связь с ресурсами и музыкантами
     */
    public function socialable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Обратная совместимость: связь с ресурсом (для старых данных)
     */
    public function resource(): MorphTo
    {
        return $this->morphTo('socialable');
    }
}
