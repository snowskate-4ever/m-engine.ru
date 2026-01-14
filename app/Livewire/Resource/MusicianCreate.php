<?php

namespace App\Livewire\Resource;

use Livewire\Component;
use App\Models\Resource;
use App\Models\Musician;
use App\Models\Type;
use App\Models\User;
use App\Models\Instrument;
use App\Models\Genre;
use Livewire\Attributes\Validate;

class MusicianCreate extends Component
{
    public $type_id = null;
    
    public bool $active = true;
    
    #[Validate('required|date')]
    public string $start_at = '';
    
    #[Validate('nullable|date|after_or_equal:start_at')]
    public ?string $end_at = null;
    
    // Основные поля
    #[Validate('required|string|max:255|unique:musicians,name')]
    public string $name = '';
    
    #[Validate('required|string')]
    public string $description = '';
    
    // Пользователь
    public ?int $user_id = null;
    
    // Профессиональные характеристики
    public ?string $bio = null;
    
    // Личная информация
    public ?string $birth_date = null;
    public ?string $gender = null;
    public ?string $education = null;
    
    // Доступность
    public bool $available_for_booking = true;
    public bool $is_session = false;
    
    // Дополнительно
    public ?string $notes = null;
    
    // Связи (для будущего использования)
    public array $instrument_ids = [];
    public array $genre_ids = [];

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
            'name' => ['required', 'string', 'max:255', 'unique:musicians,name'],
            'description' => ['required', 'string'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'bio' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'education' => ['nullable', 'string'],
            'available_for_booking' => ['sometimes', 'boolean'],
            'is_session' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ], [
            'type_id.required' => 'Тип обязателен.',
            'type_id.exists' => 'Выбранный тип не существует.',
            'start_at.required' => 'Дата начала обязательна.',
            'end_at.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Музыкант с таким названием уже существует.',
            'description.required' => 'Описание обязательно.',
        ]);
        
        // Создание музыканта
        $musician = Musician::create([
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'active' => $this->active,
            'bio' => $this->bio,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'education' => $this->education,
            'available_for_booking' => $this->available_for_booking,
            'is_session' => $this->is_session,
            'notes' => $this->notes,
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
        $users = User::orderBy('name')->get();
        $instruments = Instrument::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $genres = Genre::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        
        return view('livewire.resource.musician-create', [
            'users' => $users,
            'instruments' => $instruments,
            'genres' => $genres,
        ]);
    }
}
