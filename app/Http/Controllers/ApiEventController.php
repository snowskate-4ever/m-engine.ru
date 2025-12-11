<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiEventService;

class ApiEventController extends Controller
{
    public function get_events(Request $request)
    {
        return ApiEventService::get_events($request);
    }

    public function create_event(Request $request)
    {
        return ApiEventService::create_event($request);
    }

    public function get_event(int $id)
    {
        return ApiEventService::get_event($id);
    }

    public function edit_event(int $id, Request $request)
    {
        return ApiEventService::edit_event($id, $request);
    }

    public function delete_event(int $id)
    {
        return ApiEventService::delete_event($id);
    }
}

