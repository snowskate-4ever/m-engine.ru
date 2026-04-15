<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchingControlSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

final class MatchingRunController extends Controller
{
    public function form(): View
    {
        $this->authorizeAdmin();
        $settings = MatchingControlSetting::instance();

        return view('admin.matching-run', [
            'defaults' => [
                'scope' => (string) $settings->default_scope,
                'dry_run' => true,
                'max_requests' => null,
                'explanation_level' => 'summary',
            ],
            'lastOutput' => session('matching_run_output'),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'scope' => ['required', 'string', 'in:all,profiles,entities'],
            'dry_run' => ['nullable', 'boolean'],
            'max_requests' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'explanation_level' => ['required', 'string', 'in:off,summary,full'],
        ]);

        $args = [
            '--scope' => (string) $validated['scope'],
            '--manual' => true,
            '--run-by-user-id' => (string) $request->user()->id,
            '--explanation-level' => (string) $validated['explanation_level'],
        ];

        if ((bool) ($validated['dry_run'] ?? false)) {
            $args['--dry-run'] = true;
        }
        if (isset($validated['max_requests']) && $validated['max_requests'] !== null) {
            $args['--max-requests'] = (int) $validated['max_requests'];
        }

        Artisan::call('music:run-matching', $args);

        return redirect()
            ->route('admin.matching.run')
            ->with('matching_run_output', Artisan::output())
            ->with('success', 'Matching run executed.');
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user !== null && method_exists($user, 'hasRole') && $user->hasRole('admin'), 403);
    }
}
