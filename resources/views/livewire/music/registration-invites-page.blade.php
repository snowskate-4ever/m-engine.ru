<div class="mx-auto w-full max-w-5xl space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('ui.auth.registration_invites.page_title') }}</flux:heading>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.auth.registration_invites.page_description') }}</div>
        </div>
        <flux:button type="button" variant="primary" wire:click="createInvite">
            {{ __('ui.auth.registration_invites.create') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.auth.registration_invites.table_created_at') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.auth.registration_invites.table_status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.auth.registration_invites.table_used_at') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.auth.registration_invites.table_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($invites as $invite)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $invite['created_at'] }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $invite['status_label'] }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $invite['used_at'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if ($invite['is_active'] && filled($invite['registration_url']))
                                    <div x-data="{ copied: false }" class="inline-flex">
                                        <flux:button
                                            size="xs"
                                            type="button"
                                            x-on:click='navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($invite["registration_url"]) }}).then(() => { copied = true; setTimeout(() => copied = false, 1500); })'
                                        >
                                            <span x-show="!copied">{{ __('ui.auth.registration_invites.copy_link') }}</span>
                                            <span x-show="copied" x-cloak>{{ __('ui.copied') }}</span>
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('ui.auth.registration_invites.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
