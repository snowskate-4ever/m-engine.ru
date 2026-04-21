<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6 border-t border-zinc-200 pt-8 dark:border-zinc-700">
    <div class="relative">
        <flux:heading size="lg">{{ __('ui.account_settings.delete_section_title') }}</flux:heading>
        <flux:subheading class="mt-1 text-zinc-600 dark:text-zinc-400">{{ __('ui.account_settings.delete_section_subheading') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" data-test="delete-user-button">
            {{ __('ui.account_settings.delete_button') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('ui.account_settings.delete_modal_title') }}</flux:heading>

                <flux:subheading class="mt-2">
                    {{ __('ui.account_settings.delete_modal_body') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('ui.account_settings.delete_modal_password')" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('ui.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                    {{ __('ui.account_settings.delete_button') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
