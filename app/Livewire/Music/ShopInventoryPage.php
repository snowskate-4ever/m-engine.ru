<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Enums\ShopItemCondition;
use App\Models\Good;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\ShopItemImage;
use App\Services\Music\ShopInventorySpreadsheetImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ShopInventoryPage extends Component
{
    use WithFileUploads;

    public int $shopId;

    public ?int $editingId = null;

    public string $code = '';

    public string $condition = '';

    /** @var int|string|null */
    public $good_id = null;

    public string $price = '0';

    public string $stock_quantity = '0';

    public string $title_override = '';

    public string $description_override = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photoUploads = [];

    public mixed $importFile = null;

    public function mount(int $shopId): void
    {
        $shop = Shop::query()->whereKey($shopId)->firstOrFail();
        Gate::authorize('update', $shop);
        $this->shopId = $shopId;
        $this->condition = ShopItemCondition::New->value;
    }

    public function runImport(ShopInventorySpreadsheetImporter $importer): void
    {
        $shop = $this->resolveShop();
        Gate::authorize('update', $shop);

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $path = $this->importFile->getRealPath();
        if ($path === false || ! is_readable($path)) {
            $this->addError('importFile', __('ui.music.shop_import_unreadable'));

            return;
        }

        $result = $importer->import($shop, $path);
        $this->importFile = null;

        session()->flash('import_stats', [
            'created' => $result->created,
            'updated' => $result->updated,
            'skipped' => $result->skipped,
            'errors' => $result->errors,
        ]);
    }

    public function startCreate(): void
    {
        $this->editingId = null;
        $this->reset(['code', 'title_override', 'description_override', 'photoUploads']);
        $this->good_id = null;
        $this->condition = ShopItemCondition::New->value;
        $this->price = '0';
        $this->stock_quantity = '0';
    }

    public function editItem(int $id): void
    {
        $shop = $this->resolveShop();
        $item = ShopItem::query()->where('shop_id', $shop->id)->whereKey($id)->firstOrFail();
        Gate::authorize('update', $shop);

        $this->editingId = $item->id;
        $this->code = (string) $item->code;
        $this->condition = $item->condition instanceof ShopItemCondition ? $item->condition->value : (string) $item->condition;
        $this->good_id = $item->good_id !== null ? (int) $item->good_id : null;
        $this->price = (string) $item->price;
        $this->stock_quantity = (string) $item->stock_quantity;
        $this->title_override = (string) ($item->title_override ?? '');
        $this->description_override = (string) ($item->description_override ?? '');
        $this->photoUploads = [];
    }

    public function deleteShopImage(int $imageId): void
    {
        $shop = $this->resolveShop();
        Gate::authorize('update', $shop);

        $image = ShopItemImage::query()
            ->whereKey($imageId)
            ->whereHas('shopItem', fn ($q) => $q->where('shop_id', $shop->id))
            ->firstOrFail();

        $image->delete();
    }

    public function saveItem(): void
    {
        $shop = $this->resolveShop();
        Gate::authorize('update', $shop);

        $this->good_id = $this->good_id === '' || $this->good_id === null
            ? null
            : (int) $this->good_id;

        $codeRules = [
            'required',
            'string',
            'max:191',
            Rule::unique('shop_items', 'code')
                ->where('shop_id', $shop->id)
                ->ignore($this->editingId),
        ];

        $rules = [
            'code' => $codeRules,
            'condition' => ['required', Rule::enum(ShopItemCondition::class)],
            'good_id' => ['required', 'integer', 'exists:goods,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'title_override' => ['nullable', 'string', 'max:255'],
            'description_override' => ['nullable', 'string'],
            'photoUploads' => ['nullable', 'array', 'max:8'],
            'photoUploads.*' => ['image', 'max:5120'],
        ];

        $validated = $this->validate($rules);

        $isUsed = $validated['condition'] instanceof ShopItemCondition
            ? $validated['condition'] === ShopItemCondition::Used
            : $validated['condition'] === ShopItemCondition::Used->value;

        if (! empty($this->photoUploads) && ! $isUsed) {
            $this->addError('photoUploads', __('ui.music.shop_inventory_photos_used_only'));

            return;
        }

        $payload = [
            'shop_id' => $shop->id,
            'code' => $validated['code'],
            'condition' => $validated['condition'],
            'good_id' => $validated['good_id'] ?? null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'title_override' => $validated['title_override'] ?: null,
            'description_override' => $validated['description_override'] ?: null,
        ];

        if ($this->editingId === null) {
            $item = ShopItem::query()->create($payload);
        } else {
            $item = ShopItem::query()->where('shop_id', $shop->id)->whereKey($this->editingId)->firstOrFail();
            $item->update($payload);
        }

        $conditionEnum = $item->condition instanceof ShopItemCondition
            ? $item->condition
            : ShopItemCondition::from((string) $item->condition);

        if ($conditionEnum === ShopItemCondition::New) {
            $item->images()->get()->each->delete();
        }

        if ($conditionEnum === ShopItemCondition::Used && ! empty($this->photoUploads)) {
            $maxOrder = (int) $item->images()->max('sort_order');
            foreach ($this->photoUploads as $file) {
                $maxOrder++;
                $path = $file->store('shop_items/'.$item->id, 'public');
                $item->images()->create([
                    'path' => $path,
                    'sort_order' => $maxOrder,
                ]);
            }
        }

        $this->photoUploads = [];
        $this->startCreate();
        session()->flash('inventory_success', __('ui.music.shop_inventory_saved'));
    }

    public function deleteItem(int $id): void
    {
        $shop = $this->resolveShop();
        Gate::authorize('update', $shop);
        ShopItem::query()->where('shop_id', $shop->id)->whereKey($id)->firstOrFail()->delete();
        if ($this->editingId === $id) {
            $this->startCreate();
        }
    }

    public function render(): View
    {
        $shop = $this->resolveShop();
        Gate::authorize('update', $shop);

        $items = $shop->items()->with(['good', 'images'])->orderBy('code')->get();

        $goods = Good::query()
            ->orderBy('name')
            ->limit(400)
            ->get(['id', 'name', 'code']);

        $editingModel = $this->editingId !== null
            ? $items->firstWhere('id', $this->editingId)
            : null;

        return view('livewire.music.shop-inventory-page', [
            'shop' => $shop,
            'items' => $items,
            'goods' => $goods,
            'conditions' => ShopItemCondition::cases(),
            'editingModel' => $editingModel,
        ]);
    }

    private function resolveShop(): Shop
    {
        return Shop::query()
            ->whereKey($this->shopId)
            ->where('owner_user_id', Auth::id())
            ->firstOrFail();
    }
}
