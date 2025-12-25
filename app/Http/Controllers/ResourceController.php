<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResourceService;
class ResourceController extends Controller
{
    public function get_resources(Request $request, ResourceService $resourceService)
    {
        return $resourceService->get_resources($request);
    }

    public function create_resources(Request $request, ResourceService $resourceService)
    {
        return $resourceService->create_resources($request);
    }
    
    public function get__resource(integer $id, ResourceService $resourceService)
    {
        return $resourceService->get_resource($id);
    }
    
    public function edit_resource(integer $id, Request $request, ResourceService $resourceService)
    {
        return $resourceService->edit_resource($id, $request);
    }
    
    public function delete_resource(integer $id, ResourceService $resourceService)
    {
        return $resourceService->delete_resource($id);
    }
}
