<?php

namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UpdateProfile extends Component
{
    use WithFileUploads;

    public $user;
    public $name;
    public $email;
    public $phone;
    public $showSuccess = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,',
        'phone' => 'nullable|string|max:20',
    ];

    public function mount()
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
        dd($this);
    }

    public function updated($propertyName)
    {
        if ($propertyName !== 'avatar') {
            $this->validateOnly($propertyName);
        }
    }

    public function save()
    {
        // Обновляем правило unique для email
        $this->rules['email'] .= $this->user->id;

        $validatedData = $this->validate();

        // Обновляем данные пользователя
        $this->user->update($validatedData);

        // Показываем сообщение об успехе
        $this->showSuccess = true;

        // Скрываем сообщение через 3 секунды
        $this->dispatchBrowserEvent('profile-updated');
        
        // Обновляем сессию, если имя изменилось
        if (auth()->user()->name !== $this->name) {
            auth()->user()->refresh();
        }
    }

    public function removeAvatar()
    {
        if ($this->user->avatar_path) {
            Storage::disk('public')->delete($this->user->avatar_path);
            $this->user->update(['avatar_path' => null]);
            $this->avatarUrl = null;
            $this->dispatchBrowserEvent('avatar-removed');
        }
    }

    public function render()
    {
        return view('livewire.profile.update-profile', [
            'showSuccess' => '',
            'name' => $this->name,
            'email' => $this->email,
        ]);
    }
}