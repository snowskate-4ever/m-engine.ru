<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKanbanCalendarSetting extends Model
{
    protected $fillable = [
        'user_id',
        'show_card_created_events',
        'show_due_events',
        'show_column_move_events',
        'column_moves_include_all_targets',
        'column_move_target_ids',
    ];

    protected function casts(): array
    {
        return [
            'show_card_created_events' => 'boolean',
            'show_due_events' => 'boolean',
            'show_column_move_events' => 'boolean',
            'column_moves_include_all_targets' => 'boolean',
            'column_move_target_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, UserKanbanCalendarSetting>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Настройки календаря канбана: одна строка на пользователя, значения по умолчанию при первом обращении.
     */
    public static function forUser(User $user): self
    {
        return self::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'show_card_created_events' => true,
                'show_due_events' => true,
                'show_column_move_events' => true,
                'column_moves_include_all_targets' => true,
                'column_move_target_ids' => [],
            ],
        );
    }
}
