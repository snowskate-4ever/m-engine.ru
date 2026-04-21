<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    <x-settings.layout :subheading="__('ui.account_settings.appearance_subheading')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('ui.account_settings.appearance_light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('ui.account_settings.appearance_dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('ui.account_settings.appearance_system') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>
