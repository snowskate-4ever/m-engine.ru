<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationRuleExecution extends Model
{
    protected $fillable = [
        'automation_preset_setting_id',
        'trigger_event',
        'subject_type',
        'subject_id',
        'is_success',
        'payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_success' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function preset(): BelongsTo
    {
        return $this->belongsTo(AutomationPresetSetting::class, 'automation_preset_setting_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
