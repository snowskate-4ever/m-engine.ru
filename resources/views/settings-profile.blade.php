<x-layouts.second_level_layout :title="__('ui.profile')" :buttons="$buttons">
    @livewire('profile.update-profile', [ 
        $data = [
            'name'=> '11111',
            'email'=> '2222',
        ]
    ])
</x-layouts.second_level_layout>