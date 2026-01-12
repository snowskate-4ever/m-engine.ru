<?php

namespace App\Livewire\Event;

use Livewire\Component;
use App\Models\Event;
use Illuminate\Http\Request;

class EventList extends Component
{
    public $events = [];

    public function mount(Request $request)
    {
        $this->events = Event::with(['bookedResource', 'bookingResource', 'room.resource', 'user'])
            ->orderByDesc('start_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Event $event) => $this->formatEvent($event))
            ->toArray();
    }

    protected function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'active' => $event->active,
            'status' => $event->status,
            'booking_resource' => $event->bookingResource ? $event->bookingResource->name : null,
            'booked_resource' => $event->bookedResource ? $event->bookedResource->name : null,
            'room' => $event->room ? ($event->room->name . ($event->room->resource ? ' (' . $event->room->resource->name . ')' : '')) : null,
            'user' => $event->user ? ($event->user->name . ' (' . $event->user->email . ')') : null,
            'start_at' => $event->start_at ? $event->start_at->format('H:i d.m.Y') : null,
            'end_at' => $event->end_at ? $event->end_at->format('H:i d.m.Y') : null,
            'price' => $event->price,
            'notes' => $event->notes,
            'created_at' => $event->created_at ? $event->created_at->format('H:i d.m.Y') : null,
            'updated_at' => $event->updated_at ? $event->updated_at->format('H:i d.m.Y') : null,
        ];
    }

    public function render()
    {
        return view('event.event-list');
    }
}