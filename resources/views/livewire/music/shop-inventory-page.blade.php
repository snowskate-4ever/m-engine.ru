<div class="mx-auto w-full max-w-4xl space-y-8">
    @if (session('inventory_success'))
        <flux:callout variant="success">{{ session('inventory_success') }}</flux:callout>
    @endif

    @if (session('import_stats'))
        @php $st = session('import_stats'); @endphp
        <flux:callout variant="success">
            {{ __('ui.music.shop_import_done', ['created' => $st['created'], 'updated' => $st['updated'], 'skipped' => $st['skipped']]) }}
        </flux:callout>
        @if (! empty($st['errors']))
            <flux:callout variant="danger">
                <ul class="list-inside list-disc text-sm space-y-1">
                    @foreach (array_slice($st['errors'], 0, 25) as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                    @if (count($st['errors']) > 25)
                        <li>{{ __('ui.music.shop_import_more_errors', ['n' => count($st['errors']) - 25]) }}</li>
                    @endif
                </ul>
            </flux:callout>
        @endif
    @endif

    <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ __('ui.music.shop_import_title') }}</flux:heading>
        <flux:description>{{ __('ui.music.shop_import_hint') }}</flux:description>
        <div class="flex flex-wrap gap-3">
            <flux:button :href="route('music.shops.inventory.template', $shop)" variant="ghost">{{ __('ui.music.shop_import_template') }}</flux:button>
        </div>
        <form wire:submit="runImport" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <flux:field class="min-w-0 flex-1">
                <flux:label>{{ __('ui.music.shop_import_file') }}</flux:label>
                <input
                    type="file"
                    wire:model="importFile"
                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-900 hover:file:bg-zinc-300 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-100 dark:hover:file:bg-zinc-600"
                />
                <flux:error name="importFile" />
            </flux:field>
            <flux:button type="submit" variant="primary">{{ __('ui.music.shop_import_run') }}</flux:button>
        </form>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('ui.music.shop_inventory_title') }}</flux:heading>
            <flux:description>{{ __('ui.music.shop_inventory_hint', ['name' => $shop->name]) }}</flux:description>
        </div>
        <flux:button :href="route('music.shops.edit', $shop)" variant="ghost" wire:navigate>{{ __('ui.music.shop_inventory_back') }}</flux:button>
    </div>

    <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ $editingId ? __('ui.music.shop_inventory_edit') : __('ui.music.shop_inventory_new') }}</flux:heading>

        <form wire:submit="saveItem" class="grid gap-4 sm:grid-cols-2">
            <flux:field class="sm:col-span-2">
                <flux:label>{{ __('ui.music.shop_inventory_sku') }}</flux:label>
                <flux:input wire:model="code" type="text" autocomplete="off" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.shop_inventory_condition') }}</flux:label>
                <select
                    wire:model="condition"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    @foreach ($conditions as $c)
                        <option value="{{ $c->value }}">{{ __('ui.music.shop_item_condition.' . $c->value) }}</option>
                    @endforeach
                </select>
                <flux:error name="condition" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.shop_inventory_catalog_good') }}</flux:label>
                <select
                    wire:model.live="good_id"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="">{{ __('ui.music.shop_inventory_pick_catalog') }}</option>
                    @foreach ($goods as $g)
                        <option value="{{ $g->id }}">{{ $g->name ?? $g->code ?? ('#'.$g->id) }}</option>
                    @endforeach
                </select>
                <flux:description>{{ __('ui.music.shop_inventory_catalog_good_hint') }}</flux:description>
                <flux:error name="good_id" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.shop_inventory_price') }}</flux:label>
                <flux:input wire:model="price" type="text" inputmode="decimal" />
                <flux:error name="price" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.shop_inventory_stock') }}</flux:label>
                <flux:input wire:model="stock_quantity" type="number" min="0" step="1" />
                <flux:error name="stock_quantity" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <flux:label>{{ __('ui.music.shop_inventory_title_override') }}</flux:label>
                <flux:input wire:model="title_override" type="text" />
                <flux:error name="title_override" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <flux:label>{{ __('ui.music.shop_inventory_desc_override') }}</flux:label>
                <flux:textarea wire:model="description_override" rows="3" />
                <flux:error name="description_override" />
            </flux:field>

            @if (($condition ?? '') === \App\Enums\ShopItemCondition::Used->value)
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('ui.music.shop_inventory_photos') }}</flux:label>
                    <flux:description>{{ __('ui.music.shop_inventory_photos_hint') }}</flux:description>
                    <input
                        type="file"
                        wire:model="photoUploads"
                        accept="image/*"
                        multiple
                        class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-900 hover:file:bg-zinc-300 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-100 dark:hover:file:bg-zinc-600"
                    />
                    <flux:error name="photoUploads" />
                </flux:field>

                @if ($editingModel && $editingModel->images->isNotEmpty())
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.shop_inventory_photos_current') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($editingModel->images as $img)
                                <div class="group relative size-16 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->path) }}" alt="" class="size-full object-cover" />
                                    <button
                                        type="button"
                                        wire:click="deleteShopImage({{ $img->id }})"
                                        wire:confirm="{{ __('ui.music.shop_inventory_photo_delete_confirm') }}"
                                        class="absolute inset-0 flex items-center justify-center bg-black/50 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100"
                                    >{{ __('ui.delete') }}</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
                @if ($editingId !== null)
                    <flux:button type="button" wire:click="startCreate" variant="ghost">{{ __('ui.music.shop_inventory_cancel_edit') }}</flux:button>
                @endif
            </div>
        </form>
    </div>

    <div class="space-y-3">
        <flux:heading size="md">{{ __('ui.music.shop_inventory_list') }}</flux:heading>
        <ul class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900">
            @forelse ($items as $row)
                <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start sm:justify-between">
                    @php
                        $thumb = $row->publicPrimaryImageUrl();
                    @endphp
                    @if ($thumb)
                        <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                            <img src="{{ $thumb }}" alt="" class="size-full object-cover" loading="lazy" />
                        </div>
                    @endif
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->displayTitle() }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('ui.music.shop_inventory_sku') }}: {{ $row->code }}
                            · {{ __('ui.music.shop_item_condition.' . ($row->condition instanceof \App\Enums\ShopItemCondition ? $row->condition->value : $row->condition)) }}
                            · {{ __('ui.music.shop_inventory_price') }}: {{ $row->price }}
                            · {{ __('ui.music.shop_inventory_stock') }}: {{ $row->stock_quantity }}
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <flux:button size="sm" type="button" wire:click="editItem({{ $row->id }})">{{ __('ui.edit') }}</flux:button>
                        <flux:button size="sm" type="button" wire:click="deleteItem({{ $row->id }})" wire:confirm="{{ __('ui.music.shop_inventory_delete_confirm') }}" variant="ghost">{{ __('ui.delete') }}</flux:button>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.shop_inventory_empty') }}</li>
            @endforelse
        </ul>
    </div>
</div>
