<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PublicProfileReport;
use App\Support\Moderation\ModerationAuditRecorder;

final class PublicProfileReportAuditObserver
{
    public function created(PublicProfileReport $report): void
    {
        $status = $report->status;
        ModerationAuditRecorder::record(
            $report,
            'public_profile_report.created',
            [],
            [
                'reporter_user_id' => $report->reporter_user_id,
                'reportable_type' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
                'reason' => $report->reason,
                'status' => $status instanceof \BackedEnum ? $status->value : (string) $status,
            ],
        );
    }

    public function updated(PublicProfileReport $report): void
    {
        $old = [];
        $new = [];
        foreach (['status', 'admin_notes'] as $key) {
            if ($report->wasChanged($key)) {
                $old[$key] = $report->getOriginal($key);
                $new[$key] = $report->getAttribute($key);
                if ($old[$key] instanceof \BackedEnum) {
                    $old[$key] = $old[$key]->value;
                }
                if ($new[$key] instanceof \BackedEnum) {
                    $new[$key] = $new[$key]->value;
                }
            }
        }

        if ($old !== [] || $new !== []) {
            ModerationAuditRecorder::record($report, 'public_profile_report.updated', $old, $new);
        }
    }
}
