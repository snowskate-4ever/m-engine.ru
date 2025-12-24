<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
use App\Livewire\Profile;

class SettingsController extends Controller
{
    public function profile(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.profile'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'profile',
                'buttons' => [],
            ]
        ]);
    }

    public function password(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.password_edit'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'password',
                'buttons' => [],
            ]
        ]);
    }

    public function appearance(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' =>  __('ui.appearance_edit'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'appearance',
                'buttons' => [],
            ]
        ]);
    }

    public function two_factor(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.two_factor'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'appearance',
                'buttons' => [],
            ]
        ]);
    }
}
