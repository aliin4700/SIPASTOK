<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = $this->cart()->load('items.product.category');

        return view('cart.index', [
            'judul' => 'Keranjang',
            'cart' => $cart,
        ]);
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:' . max(1, $product->stock)],
        ]);

        if ($product->stock < 1) {
            return back()->with('error', 'Stok produk sedang kosong.');
        }

        $qty = $validated['qty'] ?? 1;
        $cart = $this->cart();
        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
        ]);

        $item->qty = min(($item->exists ? $item->qty : 0) + $qty, $product->stock);
        $item->save();

        return redirect()->route('cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, int $item)
    {
        $cartItem = $this->cart()->items()->with('product')->findOrFail($item);

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:' . max(1, $cartItem->product->stock)],
        ]);

        $cartItem->update([
            'qty' => $validated['qty'],
        ]);

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil diperbarui.');
    }

    public function destroy(int $item)
    {
        $this->cart()->items()->findOrFail($item)->delete();

        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }

    private function cart(): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }
}
