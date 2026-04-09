<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Models\ShopCartItem;
use App\Models\ShopItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddToShopCart extends Component
{
    public int $shopItemId;

    public int $quantity = 1;

    public ?string $notice = null;

    public function add(): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();
        $item = ShopItem::query()->with('shop')->findOrFail($this->shopItemId);

        $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        if ($this->quantity > $item->stock_quantity) {
            $this->addError('quantity', __('ui.music.shop_cart_max_stock', ['max' => $item->stock_quantity]));

            return;
        }

        $line = ShopCartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
        ]);

        $next = ($line->exists ? (int) $line->quantity : 0) + $this->quantity;

        if ($next > $item->stock_quantity) {
            $this->addError('quantity', __('ui.music.shop_cart_max_stock', ['max' => $item->stock_quantity]));

            return;
        }

        $line->quantity = $next;
        $line->save();

        $this->quantity = 1;
        $this->notice = __('ui.music.shop_cart_added');
    }

    public function render(): View
    {
        $item = ShopItem::query()->find($this->shopItemId);

        return view('livewire.music.add-to-shop-cart', [
            'shopItem' => $item,
            'isAuthed' => Auth::check(),
        ]);
    }
}
