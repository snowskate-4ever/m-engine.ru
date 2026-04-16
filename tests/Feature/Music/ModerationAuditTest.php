<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\ModerationStatus;
use App\Models\ModerationAudit;
use App\Models\Musician;
use App\Models\PublicProfileReport;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ModerationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderation_field_change_writes_audit_with_actor(): void
    {
        $user = User::factory()->create();
        $musician = Musician::query()->create([
            'name' => 'Audit Musician',
            'description' => null,
            'user_id' => $user->id,
            'slug' => 'aud-m-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $this->actingAs($user);
        $musician->moderation_hidden_at = now();
        $musician->save();

        $this->assertDatabaseHas('moderation_audits', [
            'auditable_type' => $musician->getMorphClass(),
            'auditable_id' => $musician->id,
            'action' => 'music_profile.moderation_updated',
            'actor_type' => User::class,
            'actor_id' => $user->id,
        ]);
    }

    public function test_moderation_status_change_writes_audit(): void
    {
        $user = User::factory()->create();
        $musician = Musician::query()->create([
            'name' => 'Status Musician',
            'description' => null,
            'user_id' => $user->id,
            'slug' => 'st-m-'.uniqid('', true),
            'public_page_enabled' => true,
            'moderation_status' => ModerationStatus::Approved->value,
        ]);

        $this->actingAs($user);
        $musician->moderation_status = ModerationStatus::Pending;
        $musician->save();

        $this->assertDatabaseHas('moderation_audits', [
            'auditable_type' => $musician->getMorphClass(),
            'auditable_id' => $musician->id,
            'action' => 'music_profile.moderation_updated',
        ]);
    }

    public function test_public_profile_report_creates_audit_row(): void
    {
        $reporter = User::factory()->create();
        $teacher = Teacher::query()->create([
            'name' => 'Reported',
            'description' => 'D',
            'slug' => 'rep-audit-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $before = ModerationAudit::query()->count();

        $this->actingAs($reporter)->post(route('music.report-profile'), [
            'reportable_type' => Teacher::class,
            'reportable_id' => $teacher->id,
            'reason' => 'This is a long enough reason for moderation audit test.',
        ])->assertRedirect();

        $this->assertSame($before + 1, ModerationAudit::query()->count());

        $report = PublicProfileReport::query()->first();
        $this->assertNotNull($report);
        $this->assertDatabaseHas('moderation_audits', [
            'auditable_type' => $report->getMorphClass(),
            'auditable_id' => $report->id,
            'action' => 'public_profile_report.created',
        ]);
    }
}
