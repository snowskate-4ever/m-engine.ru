<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\PublicProfileReportStatus;
use App\Models\PublicProfileReport;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicProfileReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_report_on_public_teacher(): void
    {
        $reporter = User::factory()->create();
        $teacher = Teacher::query()->create([
            'name' => 'Rep Teacher',
            'description' => 'D',
            'slug' => 'rep-t-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $this->actingAs($reporter)->post(route('music.report-profile'), [
            'reportable_type' => Teacher::class,
            'reportable_id' => $teacher->id,
            'reason' => 'This is a long enough reason for the report.',
        ])->assertRedirect();

        $this->assertDatabaseHas('public_profile_reports', [
            'reporter_user_id' => $reporter->id,
            'reportable_type' => Teacher::class,
            'reportable_id' => $teacher->id,
            'status' => PublicProfileReportStatus::Pending->value,
        ]);
    }

    public function test_guest_cannot_submit_report(): void
    {
        $teacher = Teacher::query()->create([
            'name' => 'Hidden',
            'description' => null,
            'slug' => 'hid-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $this->post(route('music.report-profile'), [
            'reportable_type' => Teacher::class,
            'reportable_id' => $teacher->id,
            'reason' => 'This is a long enough reason for the report.',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, PublicProfileReport::query()->count());
    }
}
