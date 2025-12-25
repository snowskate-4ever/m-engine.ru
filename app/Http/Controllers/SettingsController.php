<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Services\SettingsService;
use App\Livewire\Profile;

class SettingsController extends Controller
{
    public function profile(Request $request, SettingsService $settingsService)
    {
        return $settingsService->profile($request);
    }

    public function password(Request $request, SettingsService $settingsService)
    {
        return $settingsService->password($request);
    }

    public function appearance(Request $request, SettingsService $settingsService)
    {
        return $settingsService->appearance($request);
    }

    public function two_factor(Request $request, SettingsService $settingsService)
    {
        return $settingsService->two_factor($request);
    }
}
