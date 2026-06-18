<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class CheckoutController extends Controller
{
    private const LOCAL_DELIVERY_FEE = 10000;

    public function index()
    {
        $cart = $this->cart()->load('items.product.category');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        return view('v_checkout.index', [
            'judul' => 'Checkout',
            'cart' => $cart,
            'subtotal' => $this->subtotal($cart),
            'totalWeight' => $this->totalWeight($cart),
            'localDeliveryFee' => self::LOCAL_DELIVERY_FEE,
        ]);
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'transaction_method' => ['required', Rule::in(['pickup', 'local_delivery', 'shipping'])],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'midtrans'])],
            'address' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'delivery_address' => ['nullable', 'required_if:transaction_method,local_delivery', 'string', 'max:1000'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
            'courier' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:50'],
            'service' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:100'],
            'shipping_cost' => ['nullable', 'required_if:transaction_method,shipping', 'numeric', 'min:0'],
            'estimated_delivery' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validated['payment_method'] === 'cash' && ! in_array($validated['transaction_method'], ['pickup', 'local_delivery'], true)) {
            return back()
                ->withInput()
                ->with('error', 'Cash hanya tersedia untuk pickup dan local delivery.');
        }

        $cart = $this->cart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        foreach ($cart->items as $item) {
            if ((int) $item->quantity > (int) $item->product->stock) {
                return redirect()->route('cart.index')
                    ->with('error', 'Stok ' . $item->product->name . ' tidak mencukupi.');
            }
        }

        try {
            $order = DB::transaction(function () use ($cart, $validated) {
                $cart->load('items.product');
                $subtotal = $this->subtotal($cart);
                $totalWeight = $this->totalWeight($cart);
                $shippingCost = $this->shippingCost($validated);
                $paymentStatus = 'pending';
                $orderStatus = $validated['payment_method'] === 'cash' ? 'pending' : 'waiting_payment';

                $order = Order::create([
                    'customer_id' => $this->customer()->id,
                    'order_number' => $this->orderNumber(),
                    'status' => $orderStatus,
                    'transaction_method' => $validated['transaction_method'],
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $paymentStatus,
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'total_weight' => $totalWeight,
                    'total_price' => $subtotal + $shippingCost,
                    'address' => $validated['address'] ?? $validated['delivery_address'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($cart->items as $item) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                    if ((int) $item->quantity > (int) $product->stock) {
                        throw new RuntimeException('Stok ' . $product->name . ' tidak mencukupi.');
                    }

                    $price = (float) ($item->price ?: $product->price);

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $item->quantity,
                        'price' => $price,
                        'subtotal' => $price * $item->quantity,
                    ]);

                    $product->decrement('stock', $item->quantity);
                }

                $order->payment()->create([
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $paymentStatus,
                    'payment_proof' => null,
                    'midtrans_order_id' => null,
                    'midtrans_transaction_id' => null,
                    'midtrans_snap_token' => null,
                    'paid_at' => null,
                ]);

                $order->shipment()->create([
                    'shipping_type' => $validated['transaction_method'],
                    'courier' => $validated['transaction_method'] === 'shipping' ? ($validated['courier'] ?? null) : null,
                    'service' => $validated['transaction_method'] === 'shipping' ? ($validated['service'] ?? null) : null,
                    'tracking_number' => null,
                    'shipping_cost' => $shippingCost,
                    'estimated_delivery' => $validated['transaction_method'] === 'shipping' ? ($validated['estimated_delivery'] ?? null) : null,
                    'delivery_latitude' => null,
                    'delivery_longitude' => null,
                    'delivery_address' => $validated['transaction_method'] === 'local_delivery' ? ($validated['delivery_address'] ?? null) : null,
                    'delivery_note' => $validated['transaction_method'] === 'local_delivery' ? ($validated['delivery_note'] ?? null) : null,
                    'delivery_distance' => null,
                ]);

                $cart->items()->delete();

                return $order;
            });
        } catch (RuntimeException $exception) {
            return redirect()->route('cart.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('order.detail', $order)->with('success', 'Pesanan ' . $order->order_number . ' berhasil dibuat.');
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

    private function subtotal(Cart $cart): float
    {
        return (float) $cart->items->sum(fn (CartItem $item) => $item->subtotal());
    }

    private function totalWeight(Cart $cart): int
    {
        return (int) $cart->items->sum(fn (CartItem $item) => (int) ($item->product->weight ?? 0) * $item->quantity);
    }

    private function shippingCost(array $validated): float
    {
        return match ($validated['transaction_method']) {
            'pickup' => 0,
            'local_delivery' => self::LOCAL_DELIVERY_FEE,
            'shipping' => (float) ($validated['shipping_cost'] ?? 0),
        };
    }

    private function orderNumber(): string
    {
        do {
            $number = 'BRD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
