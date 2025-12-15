<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\api\ApiResourceService;

class ApiResourceController extends Controller
{
    public function get_resources(Request $request)
    {
        return ApiResourceService::get_resources($request);
    }

    public function create_resource(Request $request)
    {
        return ApiResourceService::create_resource($request);
    }

    public function get_resource(int $id)
    {
        return ApiResourceService::get_resource($id);
    }

    public function edit_resource(int $id, Request $request)
    {
        return ApiResourceService::edit_resource($id, $request);
    }

    public function delete_resource(int $id)
    {
        return ApiResourceService::delete_resource($id);
    }
}

