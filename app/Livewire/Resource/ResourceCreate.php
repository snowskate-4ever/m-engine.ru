<?php

namespace App\Livewire\Resource;

use Livewire\Component;
use App\Models\Type;

class ResourceCreate extends Component
{
    public $type_id = null;
    
    // Маппинг типа ресурса на компонент
    protected array $typeComponentMap = [
        'musician' => 'resource.musician-create',
        'teacher' => 'resource.teacher-create',
        'place' => 'resource.place-create',
        'rehearsal' => 'resource.rehearsal-create',
        'studio' => 'resource.studio-create',
        'peformer' => 'resource.performer-create',
    ];

    public function mount($type_id = null)
    {
        $this->type_id = $type_id ? (int)$type_id : null;
    }

    public function updatedTypeId($value)
    {
        // При изменении типа перенаправляем на страницу с новым типом
        if ($value) {
            return redirect()->route('resources.create', ['type' => $value]);
        }
    }

    public function render()
    {
        $types = Type::where('resource_type', 'resources')->get();
        $component = null;
        
        // Определяем компонент по типу
        if ($this->type_id) {
            $type = Type::find($this->type_id);
            if ($type && isset($this->typeComponentMap[$type->name])) {
                $component = $this->typeComponentMap[$type->name];
            }
        }
        
        return view('resources.resource-create', [
            'types' => $types,
            'component' => $component,
        ]);
    }
}
