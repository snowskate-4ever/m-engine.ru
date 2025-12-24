<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Attributes\Validate; 

class Profile extends Component
{
    public $user;
    
    #[Validate('required|string|max:255')] 
    public string $name = '';

    #[Validate('required|email|max:255')] 
    public string $email = '';

    #[Validate('nullable|string|max:12')] 
    public string $phone = '';

    public $showSuccess = false;

    public function mount()
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
        ]);
        
        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);
    
        session()->flash('success', 'Данные успешно сохранены!');

        $this->dispatch('profile-updated');
    }
    
    public function dispatchBrowserEvent() 
    {
        return 'Данные успешно сохранены';
    }

    public function render()
    {
        return view('profile.update-profile', [
        ]);
    }
}