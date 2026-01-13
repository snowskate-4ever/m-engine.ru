<?php

namespace App\Livewire\Resource;

use Livewire\Component;
use App\Models\Resource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ResourceList extends Component
{
    public $resources = [];
    public $search = '';
    public $type_id = null;

    public function mount(Request $request, $type_id = null)
    {
        // Получаем type_id из маршрута, если не передан как параметр
        if (!$type_id && $request->route('type_id')) {
            $type_id = $request->route('type_id');
        }
        
        $this->type_id = $type_id;
        $this->loadResources();
    }

    public function updatedSearch()
    {
        $this->loadResources();
    }

    protected function loadResources()
    {
        $query = Resource::with('type');
        
        // Фильтруем по типу, если передан type_id
        if ($this->type_id) {
            $query->where('type_id', $this->type_id);
        }
        
        // Поиск временно отключен, так как поля name и description удалены из таблицы
        // if (!empty(trim($this->search ?? ''))) {
        //     $query->search(trim($this->search));
        // }
        
        $resources = $query->get();
        
        // Форматируем данные для отображения
        $this->resources = $resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'active' => $resource->active,
                'type_name' => $resource->type ? (__('moonshine.types.values.' . $resource->type->name) ?: $resource->type->name) : '',
                'start_at' => $resource->start_at ? Carbon::parse($resource->start_at)->format('H:i d-m-Y') : '',
                'end_at' => $resource->end_at ? Carbon::parse($resource->end_at)->format('H:i d-m-Y') : '',
                'created_at' => $resource->created_at ? Carbon::parse($resource->created_at)->format('H:i d-m-Y') : '',
                'updated_at' => $resource->updated_at ? Carbon::parse($resource->updated_at)->format('H:i d-m-Y') : '',
            ];
        })->toArray();
    }

    public function render()
    {
        return view('resources.resource-list');
    }
}