<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EventService;

class EventController extends Controller
{
    public function get_events(Request $request)
    {
        return EventService::get_events($request);
    }

    public function create_events(Request $request)
    {
        return EventService::create_events($request);
    }
    
    public function get_event(int $id)
    {
        return EventService::get_event($id);
    }
    
    public function edit_event(int $id, Request $request)
    {
        return EventService::edit_event($id, $request);
    }
    
    public function delete_event(int $id)
    {
        return EventService::delete_event($id);
    }
}
