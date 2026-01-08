<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.auth.register.title')" :description="__('ui.auth.register.description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('ui.auth.register.name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('ui.auth.register.full_name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('ui.auth.register.email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('ui.auth.register.password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('ui.auth.register.password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('ui.auth.register.confirm_password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('ui.auth.register.confirm_password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('ui.auth.register.button') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('ui.auth.register.already_have_account') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('ui.auth.login.button') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
