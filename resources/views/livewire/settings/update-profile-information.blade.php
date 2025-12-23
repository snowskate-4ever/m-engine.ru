<x-layouts.second_level_layout :title="__('Profile')" :buttons="$buttons">
    <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
        {{-- Поле для имени --}}
        @if(config('flux.has_flux'))
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
        @else
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Name') }}
            </label>
            <input wire:model="name" id="name" type="text" required autofocus autocomplete="name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Поле для email --}}
        @if(config('flux.has_flux'))
            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
        @else
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Email') }}
                </label>
                <input wire:model="email" id="email" type="email" required autocomplete="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Верификация email --}}
        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
            <div>
                @if(config('flux.has_flux'))
                    <flux:text class="mt-4">
                        {{ __('Your email address is unverified.') }}
                        <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </flux:link>
                    </flux:text>
                @else
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Your email address is unverified.') }}
                        <button wire:click.prevent="resendVerificationNotification" 
                            class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                @endif

                @if (session('status') === 'verification-link-sent')
                    @if(config('flux.has_flux'))
                        <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </flux:text>
                    @else
                        <p class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                @endif
            </div>
        @endif

        {{-- Кнопки --}}
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-end">
                @if(config('flux.has_flux'))
                     <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                @else
                    <button type="submit" 
                        class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Save') }}
                    </button>
                @endif
            </div>

            @if(config('flux.has_flux'))
                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            @else
                <div class="me-3">
                    @if(session()->has('message'))
                        <p class="text-sm text-green-600 dark:text-green-400">
                            {{ session('message') }}
                        </p>
                    @endif
                    @if($this->getErrorBag()->hasAny())
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ __('Please fix the errors above.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>
    </form>
</x-layouts.second_level_layout>