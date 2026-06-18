<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = $this->cart()->load('items.product.category');
        $subtotal = $cart->items->sum(fn (CartItem $item) => $item->subtotal());
        $totalWeight = $cart->items->sum(fn (CartItem $item) => (int) ($item->product->weight ?? 0) * $item->quantity);

        return view('v_cart.index', [
            'judul' => 'Keranjang',
            'cart' => $cart,
            'subtotal' => $subtotal,
            'totalWeight' => $totalWeight,
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:' . max(1, $product->stock)],
        ]);

        if (! in_array((string) $product->status, ['published', 'active'], true)) {
            return back()->with('error', 'Produk belum tersedia untuk dibeli.');
        }

        if ((int) $product->stock < 1) {
            return back()->with('error', 'Stok produk sedang kosong.');
        }

        $quantity = $validated['quantity'] ?? 1;
        $cart = $this->cart();
        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
        ]);

        $item->quantity = min(($item->exists ? $item->quantity : 0) + $quantity, $product->stock);
        $item->price = $product->price;
        $item->save();

        return redirect()->route('cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);
        $cartItem->load('product');

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . max(1, $cartItem->product->stock)],
        ]);

        $cartItem->update([
            'quantity' => $validated['quantity'],
        ]);

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil diperbarui.');
    }

    public function remove(CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);
        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }

    public function clear()
    {
        $this->cart()->items()->delete();

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    private function cart(): Cart
    {
        return Cart::firstOrCreate([
            'customer_id' => $this->customer()->id,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }

    private function authorizeCartItem(CartItem $cartItem): void
    {
        abort_unless((int) $cartItem->cart->customer_id === (int) $this->customer()->id, 403);
    }
}
