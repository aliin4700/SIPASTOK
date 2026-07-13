<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Mengelola cart aktif customer.
 *
 * Cart di modul ini belum menjadi order; order baru dibuat saat checkout.
 */
class CartController extends Controller
{
    /**
     * Menampilkan isi keranjang customer.
     */
    public function index()
    {
        // Load produk agar subtotal dan total berat dihitung dari relasi cart item yang sama.
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

    /**
     * Menambahkan produk ke cart aktif customer.
     */
    public function add(Request $request, Product $product)
    {
        // Quantity boleh dikirim dari detail produk, sedangkan product card memakai default satu item.
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

        // Quantity digabung bila produk sudah ada di cart, tetapi tidak melewati stok produk.
        $item->quantity = min(($item->exists ? $item->quantity : 0) + $quantity, $product->stock);
        $item->price = $product->price;
        $item->save();

        return redirect()->route('cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    /**
     * Mengubah quantity item dalam cart.
     */
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

    /**
     * Menghapus satu item dari cart.
     */
    public function remove(CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);
        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }

    /**
     * Mengosongkan seluruh cart customer.
     */
    public function clear()
    {
        $this->cart()->items()->delete();

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    private function cart(): Cart
    {
        // Setiap customer hanya membutuhkan satu cart aktif pada tahap ini.
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
        // Cart item tidak boleh diubah oleh customer lain walaupun id item diketahui.
        abort_unless((int) $cartItem->cart->customer_id === (int) $this->customer()->id, 403);
    }
}
