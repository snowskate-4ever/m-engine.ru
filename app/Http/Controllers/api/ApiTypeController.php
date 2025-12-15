<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\api\ApiTypeService;

class ApiTypeController extends Controller
{
    public function get_types(Request $request)
    {
        return ApiTypeService::get_types($request);
    }

    public function create_type(Request $request)
    {
        return ApiTypeService::create_type($request);
    }

    public function get_type(int $id)
    {
        return ApiTypeService::get_type($id);
    }

    public function edit_type(int $id, Request $request)
    {
        return ApiTypeService::edit_type($id, $request);
    }

    public function delete_type(int $id)
    {
        return ApiTypeService::delete_type($id);
    }
}

