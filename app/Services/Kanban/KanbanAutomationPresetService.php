<?php

declare(strict_types=1);

namespace App\Services\Kanban;

use App\Enums\AutomationPresetType;
use App\Models\AutomationPresetSetting;
use App\Models\AutomationRuleExecution;
use Illuminate\Database\Eloquent\Model;

final class KanbanAutomationPresetService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(AutomationPresetType $preset, ?Model $subject, array $payload = []): void
    {
        $settings = AutomationPresetSetting::query()
            ->where('preset_type', $preset->value)
            ->where('is_enabled', true)
            ->get();

        foreach ($settings as $setting) {
            AutomationRuleExecution::query()->create([
                'automation_preset_setting_id' => $setting->id,
                'trigger_event' => $preset->value,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'is_success' => true,
                'payload' => $payload,
            ]);
        }
    }
}
