<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Enums\PublicProfileReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Music\StorePublicProfileReportRequest;
use App\Models\PublicProfileReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class PublicProfileReportController extends Controller
{
    public function store(StorePublicProfileReportRequest $request): RedirectResponse
    {
        $data = $request->validated();
        PublicProfileReport::query()->create([
            'reporter_user_id' => Auth::id(),
            'reportable_type' => $data['reportable_type'],
            'reportable_id' => $data['reportable_id'],
            'reason' => $data['reason'],
            'status' => PublicProfileReportStatus::Pending,
        ]);

        $request->session()->flash('profile_report_submitted', true);

        return redirect()->back();
    }
}
