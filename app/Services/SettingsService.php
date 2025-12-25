<?php

namespace App\Services;

use Illuminate\Http\Request;

class SettingsService 
{
    public array $buttons = [];

    function __construct() 
    {
        $this->buttons = [
            'settings' => [
                'profile' => 'profile',
                'password' => 'password',
                'two_factor' => 'two-factor',
                'appearance' => 'appearance',
            ]
        ];
    }

    public function profile(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.profile'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'profile',
                'buttons' => $this->buttons,
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
                'buttons' => $this->buttons,
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
                'buttons' => $this->buttons,
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
                'buttons' => $this->buttons,
            ]
        ]);
    }
}
