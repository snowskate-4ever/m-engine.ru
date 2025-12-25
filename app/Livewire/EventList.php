<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Event;
use Illuminate\Http\Request;

class EventList extends Component
{
    public $events = [];

    public function mount(Request $request)
    {
        $this->events = Event::all();
    }

    public function render()
    {
        return view('events.event-list');
    }
}