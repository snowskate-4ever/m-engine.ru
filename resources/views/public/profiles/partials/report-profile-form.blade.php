{{-- Жалоба на публичный профиль (только авторизованные) --}}
@auth
    <section class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-800" aria-label="{{ __('ui.music.report_profile_section_label') }}">
        @if (session('profile_report_submitted'))
            <flux:callout variant="success">{{ __('ui.music.report_profile_thanks') }}</flux:callout>
        @endif
        <details class="group">
            <summary class="cursor-pointer text-sm font-medium text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100">
                {{ __('ui.music.report_profile_trigger') }}
            </summary>
            <form method="post" action="{{ route('music.report-profile') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="reportable_type" value="{{ $model::class }}" />
                <input type="hidden" name="reportable_id" value="{{ $model->id }}" />
                <flux:field>
                    <flux:label>{{ __('ui.music.report_profile_reason') }}</flux:label>
                    <flux:textarea name="reason" rows="4" required minlength="10" maxlength="2000" placeholder="{{ __('ui.music.report_profile_reason_placeholder') }}" />
                    @error('reason')
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </flux:field>
                <flux:button type="submit" variant="primary" size="sm">{{ __('ui.music.report_profile_submit') }}</flux:button>
            </form>
        </details>
    </section>
@endauth
