<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KanbanAccessLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KanbanAccessGrant extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'grantee_type',
        'grantee_id',
        'access_level',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_level' => KanbanAccessLevel::class,
        ];
    }

    /**
     * @return MorphTo<KanbanColumn|KanbanCard, KanbanAccessGrant>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<User|Role, KanbanAccessGrant>
     */
    public function grantee(): MorphTo
    {
        return $this->morphTo();
    }
}
