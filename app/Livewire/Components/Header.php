<?php

namespace App\Livewire\Components;

use Livewire\Component;

class Header extends Component
{
    public $title;
 
    public function _construct()
    {
        $this->title = '1111';
    }

    public function render()
    {
        return view('livewire.components.header');
    }
}
