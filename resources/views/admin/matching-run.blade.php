<x-layouts.app :title="'Matching Run'">
    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Matching Run</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Manual matching execution with audit parameters.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.matching.run.execute') }}" class="space-y-4 rounded border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            @csrf

            <div class="space-y-1">
                <label class="text-sm font-medium">Scope</label>
                <select name="scope" class="w-full rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    @foreach (['all' => 'All', 'profiles' => 'Profiles only', 'entities' => 'Entities only'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('scope', $defaults['scope']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('scope')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium">Max requests</label>
                <input
                    type="number"
                    min="1"
                    max="10000"
                    name="max_requests"
                    value="{{ old('max_requests', $defaults['max_requests']) }}"
                    class="w-full rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="Leave empty for no limit"
                >
                @error('max_requests')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="dry_run" value="1" @checked((bool) old('dry_run', $defaults['dry_run']))>
                Dry run (do not write matches)
            </label>

            <div class="space-y-1">
                <label class="text-sm font-medium">Explanation level</label>
                <select name="explanation_level" class="w-full rounded border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    @foreach (['off' => 'Off', 'summary' => 'Summary', 'full' => 'Full'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('explanation_level', $defaults['explanation_level']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    <strong>off</strong> — минимум логов; <strong>summary</strong> — рекомендуемый баланс; <strong>full</strong> — детальная отладка (может заметно увеличить размер audit-log).
                </p>
                @error('explanation_level')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Run now
            </button>
        </form>

        @if ($lastOutput)
            <div class="rounded border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <h2 class="mb-2 text-sm font-semibold">Last command output</h2>
                <pre class="whitespace-pre-wrap text-xs">{{ $lastOutput }}</pre>
            </div>
        @endif
    </div>
</x-layouts.app>
