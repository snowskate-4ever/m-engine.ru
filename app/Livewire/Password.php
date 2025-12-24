<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Attributes\Validate; 
use Illuminate\Validation\Rules\Password as PasswordRules;

class Password extends Component
{
    #[Validate('required|email|max:255')] 
    public string $current_password = '';

    #[Validate('required|email|max:255')] 
    public string $password = '';

    #[Validate('required|email|max:255')] 
    public string $password_confirmation = '';

    public function mount()
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
    }

    public function save()
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', PasswordRules::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
    
    public function dispatchBrowserEvent() 
    {
        return 'Данные успешно сохранены';
    }

    public function render()
    {
        return view('profile.update-password', [
        ]);
    }
}