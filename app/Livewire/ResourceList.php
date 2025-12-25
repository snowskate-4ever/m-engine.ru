<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceList extends Component
{
    public $resources = [];

    public function mount(Request $request)
    {
        $this->resources = Resource::with('type')->get();
    }

    public function render()
    {
        return view('resources.resource-list');
    }
}