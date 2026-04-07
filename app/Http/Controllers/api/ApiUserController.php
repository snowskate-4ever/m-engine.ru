<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\api\ApiUserService;
use Illuminate\Http\Request;

class ApiUserController extends Controller
{
    public function get_users(Request $request)
    {
        return ApiUserService::get_users($request);
    }

    public function get_user(int $id)
    {
        return ApiUserService::get_user($id);
    }
}
