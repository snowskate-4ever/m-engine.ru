<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EventService;

class EventController extends Controller
{

    public function get_events(Request $request, EventService $eventService)
    {
        return $eventService->get_events($request);
    }

    public function create_event(Request $request, EventService $eventService)
    {
        return $eventService->create_event($request);
    }
    
    public function get_event(intenger $id, EventService $eventService)
    {
        return $eventService->get_event($id);
    }
    
    public function edit_event(intenger $id, Request $request, EventService $eventService)
    {
        return $eventService->edit_event($id, $request);
    }
    
    public function delete_event(intenger $id, EventService $eventService)
    {
        return $eventService->delete_event($id);
    }
}
