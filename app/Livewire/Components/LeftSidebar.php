<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\Type;

class LeftSidebar extends Component
{
    public function render()
    {
        $resourceTypes = Type::where('resource_type', 'resources')
            ->orderBy('name')
            ->get();

        return view('livewire.components.left-sidebar', [
            'resourceTypes' => $resourceTypes,
        ]);
    }
}
