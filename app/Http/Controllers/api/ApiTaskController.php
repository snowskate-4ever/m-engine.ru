<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\api\ApiTaskService;

class ApiTaskController extends Controller
{  
    public function get_tasks(Request $request)
    {
        return ApiTaskService::get_tasks($request);
    }

    public function create_tasks(Request $request)
    {
        return ApiTaskService::create_tasks($request);
    }
    
    public function get_task(int $id)
    {
        return ApiTaskService::get_task($id);
    }
    
    public function edit_task(int $id, Request $request)
    {
        return ApiTaskService::edit_task($id, $request);
    }
    
    public function delete_task(int $id)
    {
        return ApiTaskService::delete_task($id);
    }
}
