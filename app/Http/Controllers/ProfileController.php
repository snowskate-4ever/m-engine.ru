<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    public function get_profiles(Request $request)
    {
        return ProfileService::get_profiles($request);
    }

    public function create_profiles(Request $request)
    {
        return ProfileService::create_profiles($request);
    }
    
    public function get_profile(int $id)
    {
        return ProfileService::get_profile($id);
    }
    
    public function edit_profile(int $id, Request $request)
    {
        return ProfileService::edit_profile($id, $request);
    }
    
    public function delete_profile(int $id)
    {
        return ProfileService::delete_profile($id);
    }
}
