<?php

namespace App\Livewire\Resource;

use Livewire\Component;
use App\Models\Resource;
use App\Models\Rehersal;
use App\Models\Type;
use Livewire\Attributes\Validate;

class RehearsalCreate extends Component
{
    public $type_id = null;
    
    public bool $active = true;
    
    #[Validate('required|date')]
    public string $start_at = '';
    
    #[Validate('nullable|date|after_or_equal:start_at')]
    public ?string $end_at = null;
    
    #[Validate('required|string|max:255|unique:rehearsals,name')]
    public string $name = '';
    
    #[Validate('required|string')]
    public string $description = '';

    public function mount($type_id = null)
    {
        $this->type_id = $type_id ? (int)$type_id : null;
    }

    public function save()
    {
        $this->validate([
            'active' => ['sometimes', 'boolean'],
            'type_id' => ['required', 'integer', 'exists:types,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'name' => ['required', 'string', 'max:255', 'unique:rehearsals,name'],
            'description' => ['required', 'string'],
        ], [
            'type_id.required' => 'Тип обязателен.',
            'type_id.exists' => 'Выбранный тип не существует.',
            'start_at.required' => 'Дата начала обязательна.',
            'end_at.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Репточка с таким названием уже существует.',
            'description.required' => 'Описание обязательно.',
        ]);
        
        // Создание репточки
        $rehearsal = Rehersal::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);
        
        // Создание ресурса
        $resource = Resource::create([
            'active' => $this->active,
            'type_id' => $this->type_id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
        ]);
        
        session()->flash('success', 'Ресурс успешно создан!');
        
        if ($this->type_id) {
            return redirect()->route('resources.by_type', ['type_id' => $this->type_id]);
        } else {
            return redirect()->route('resources');
        }
    }

    public function render()
    {
        return view('livewire.resource.rehearsal-create');
    }
}
