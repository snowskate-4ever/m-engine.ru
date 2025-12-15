<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResourceService;

class ResourceController extends Controller
{
    public function get_resources(Request $request)
    {
        return ResourceService::get_resources($request);
    }

    public function create_resources(Request $request)
    {
        return ResourceService::create_resources($request);
    }
    
    public function get__resource(int $id)
    {
        return ResourceService::get_resource($id);
    }
    
    public function edit_resource(int $id, Request $request)
    {
        return ResourceService::edit_resource($id, $request);
    }
    
    public function delete_resource(int $id)
    {
        return ResourceService::delete_resource($id);
    }
}
