<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CheckoutOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function create()
    {
        $cart = $this->cart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        return view('checkout.create', [
            'judul' => 'Checkout',
            'cart' => $cart,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'string', 'max:1000'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'payment_method' => ['required', 'in:midtrans,bank_transfer,cod'],
        ]);

        $cart = $this->cart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        foreach ($cart->items as $item) {
            if ($item->qty > $item->product->stock) {
                return redirect()->route('cart.index')
                    ->with('error', 'Stok ' . $item->product->name . ' tidak mencukupi.');
            }
        }

        $order = DB::transaction(function () use ($cart, $validated) {
            $order = CheckoutOrder::create([
                'user_id' => auth()->id(),
                'total_price' => $cart->total(),
                'status' => $validated['payment_method'] === 'cod' ? 'waiting_confirmation' : 'pending_payment',
                'shipping_address' => $validated['shipping_address'],
                'shipping_postal_code' => $validated['shipping_postal_code'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_method'] === 'midtrans'
                    ? 'MIDTRANS-' . now()->format('YmdHis') . '-' . auth()->id()
                    : null,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'price' => $item->product->price,
                ]);

                $item->product->decrement('stock', $item->qty);
            }

            $cart->items()->delete();

            return $order;
        });

        return redirect()->route('checkout.success', $order)->with('success', 'Pesanan berhasil dibuat.');
    }

    public function success(CheckoutOrder $order)
    {
        abort_unless((int) $order->user_id === auth()->id(), 403);

        return view('checkout.success', [
            'judul' => 'Pesanan Dibuat',
            'order' => $order->load('items.product'),
        ]);
    }

    public function history()
    {
        $orders = CheckoutOrder::with('items.product')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('checkout.history', [
            'judul' => 'Riwayat Pesanan',
            'orders' => $orders,
        ]);
    }

    private function cart(): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }
}
