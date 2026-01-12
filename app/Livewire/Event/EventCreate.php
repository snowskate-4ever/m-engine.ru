<?php

namespace App\Livewire\Event;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Attributes\Validate; 
use App\Models\Event;

class EventCreate extends Component
{
    public $event;

    // public $resources = [];
    
    public $search = '';
    
    // #[Validate('required|string|max:255')] 
    // public string $name = '';

    // #[Validate('required|email|max:255')] 
    // public string $email = '';

    // #[Validate('nullable|string|max:12')] 
    // public string $phone = '';

    // public $showSuccess = false;

    public function mount()
    {
        $this->event = new Event();
        // $this->resources = \App\Models\Resource::all();

        // $this->name = $this->user->name;
        // $table->string('name')->unique();
        // $table->text('description');
        // $table->boolean('active');
        // $table->uuid('resource_id')->nullable();
        // $table->uuid('room_id')->nullable();
        // $table->dateTime('start_at')->nullable();
        // $table->dateTime('end_at')->nullable();
    }

    public function save()
    {
        // Валидация должна работать с event объектом, но метод пока не реализован
        // TODO: Реализовать сохранение события
        // $this->validate([
        //     'event.name' => 'required|string|max:255',
        //     'event.description' => 'nullable|string',
        //     'event.active' => 'boolean',
        //     'event.booking_resource_id' => 'nullable|exists:resources,id',
        //     'event.booked_resource_id' => 'nullable|exists:resources,id',
        //     'event.start_at' => 'nullable|date',
        //     'event.end_at' => 'nullable|date|after_or_equal:event.start_at',
        // ]);
        
        // $this->event->save();
        // session()->flash('success', 'Событие успешно создано!');
    }
    
    public function dispatchBrowserEvent() 
    {
        return 'Данные успешно сохранены';
    }

    public function render()
    {
        return view('event.event-create', [
            'resources' => \App\Models\Resource::search($this->search)->get(),
            // 'resources' => dd( $this->search, \App\Models\Resource::search($this->search)->get()),
        ]);
    }
}