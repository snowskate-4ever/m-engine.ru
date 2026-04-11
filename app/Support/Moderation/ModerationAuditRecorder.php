<?php

declare(strict_types=1);

namespace App\Support\Moderation;

use App\Models\ModerationAudit;
use Illuminate\Database\Eloquent\Model;

final class ModerationAuditRecorder
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function record(Model $auditable, string $action, array $oldValues, array $newValues): void
    {
        if ($oldValues === [] && $newValues === []) {
            return;
        }

        [$actorType, $actorId] = AuditActor::resolve();

        ModerationAudit::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'created_at' => now(),
        ]);
    }
}
