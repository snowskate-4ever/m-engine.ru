<?php

namespace App\Livewire\Event;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate; 
use App\Models\Event;
use App\Models\Resource;
use App\Models\Room;

class EventCreate extends Component
{
    #[Validate('required|string|max:255|unique:events,name')]
    public string $name = '';

    #[Validate('required|string')]
    public string $description = '';

    #[Validate('boolean')]
    public bool $active = true;

    #[Validate('nullable|exists:resources,id')]
    public ?int $resource_id = null;

    #[Validate('nullable|exists:rooms,id')]
    public ?int $room_id = null;

    #[Validate('nullable|date')]
    public ?string $start_at = null;

    #[Validate('nullable|date|after_or_equal:start_at')]
    public ?string $end_at = null;

    public $resourceSearch = '';
    public $roomSearch = '';

    public function mount()
    {
        // Инициализация пустых значений
    }

    public function save()
    {
        $this->validate();

        Event::create([
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'resource_id' => $this->resource_id,
            'room_id' => $this->room_id,
            'start_at' => $this->start_at ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $this->start_at) : null,
            'end_at' => $this->end_at ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $this->end_at) : null,
        ]);

        session()->flash('success', 'Событие успешно создано!');
        
        // Очистка формы
        $this->reset(['name', 'description', 'active', 'resource_id', 'room_id', 'start_at', 'end_at', 'resourceSearch', 'roomSearch']);
        $this->active = true; // Устанавливаем значение по умолчанию обратно
    }

    public function render()
    {
        $resourcesQuery = Resource::query()->with('type');
        
        if ($this->resourceSearch) {
            $resourcesQuery = Resource::search($this->resourceSearch)->with('type');
        } elseif ($this->resource_id) {
            $resourcesQuery->where('id', $this->resource_id);
        }
        
        $resources = $resourcesQuery->limit(10)->get();

        $roomsQuery = Room::query()->with('resource.type');
        
        if ($this->roomSearch) {
            $roomsQuery->where('name', 'LIKE', "%{$this->roomSearch}%");
        } elseif ($this->room_id) {
            $roomsQuery->where('id', $this->room_id);
        }
        
        $rooms = $roomsQuery->limit(10)->get();

        return view('event.event-create', [
            'resources' => $resources,
            'rooms' => $rooms,
        ]);
    }
}