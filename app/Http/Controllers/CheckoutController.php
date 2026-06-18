<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Services\RajaOngkirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(private RajaOngkirService $rajaOngkir)
    {
    }

    public function create()
    {
        $cart = $this->cart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        return view('checkout.create', [
            'judul' => 'Checkout',
            'cart' => $cart,
            'couriers' => RajaOngkirService::COURIERS,
            'totalWeight' => $this->cartWeight($cart),
            'midtransAvailable' => true,
        ]);
    }

    public function store(Request $request)
    {
        $transactionMethod = $request->input('transaction_method');
        $allowedPayments = match ($transactionMethod) {
            'pickup' => ['cash_pickup', 'bank_transfer'],
            'delivery' => ['cash_delivery', 'bank_transfer'],
            'shipping' => ['bank_transfer', 'midtrans'],
            default => [],
        };

        $validated = $request->validate([
            'transaction_method' => ['required', Rule::in(['pickup', 'delivery', 'shipping'])],
            'payment_method' => ['required', Rule::in($allowedPayments)],
            'shipping_address' => ['required_if:transaction_method,delivery,shipping', 'nullable', 'string', 'max:1000'],
            'shipping_postal_code' => ['required_if:transaction_method,delivery,shipping', 'nullable', 'string', 'max:20'],
            'shipping_province_id' => ['required_if:transaction_method,shipping', 'nullable', 'integer'],
            'shipping_province' => ['required_if:transaction_method,shipping', 'nullable', 'string', 'max:100'],
            'shipping_city_id' => ['required_if:transaction_method,shipping', 'nullable', 'integer'],
            'shipping_city' => ['required_if:transaction_method,shipping', 'nullable', 'string', 'max:150'],
            'shipping_courier' => ['required_if:transaction_method,shipping', 'nullable', 'in:jne,jnt,sicepat,pos'],
            'shipping_service' => ['required_if:transaction_method,shipping', 'nullable', 'string', 'max:100'],
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

        $shipping = null;
        $shippingCost = 0;

        if ($validated['transaction_method'] === 'shipping') {
            try {
                $shipping = $this->rajaOngkir->findService(
                    $validated['shipping_city_id'],
                    $validated['shipping_courier'],
                    $validated['shipping_service'],
                    $this->cartWeight($cart)
                );
            } catch (RuntimeException $exception) {
                return back()
                    ->withInput()
                    ->with('error', $exception->getMessage());
            }

            if (! $shipping) {
                return back()
                    ->withInput()
                    ->with('error', RajaOngkirService::UNAVAILABLE_MESSAGE);
            }

            $shippingCost = $shipping['cost'];
        }

        $totalWeight = $this->cartWeight($cart);

        $order = DB::transaction(function () use ($cart, $validated, $shipping, $shippingCost, $totalWeight) {
            $transactionMethod = $validated['transaction_method'] === 'delivery'
                ? 'local_delivery'
                : $validated['transaction_method'];
            $paymentMethod = str_starts_with($validated['payment_method'], 'cash')
                ? 'cash'
                : $validated['payment_method'];
            $subtotal = $cart->total();

            $order = Order::create([
                'customer_id' => $this->customer()->id,
                'order_number' => 'BRD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'subtotal' => $subtotal,
                'total_price' => $subtotal + $shippingCost,
                'status' => in_array($validated['payment_method'], ['bank_transfer', 'midtrans'], true) ? 'waiting_payment' : 'pending',
                'transaction_method' => $transactionMethod,
                'payment_method' => $paymentMethod,
                'payment_status' => in_array($paymentMethod, ['bank_transfer', 'midtrans'], true) ? 'pending' : 'pending',
                'shipping_cost' => $shippingCost,
                'total_weight' => $totalWeight,
                'address' => $validated['shipping_address'] ?? null,
                'postal_code' => $validated['shipping_postal_code'] ?? null,
            ]);

            $order->shipment()->create([
                'shipping_type' => $transactionMethod,
                'courier' => $shipping['courier_name'] ?? ($transactionMethod === 'local_delivery' ? 'Local Delivery' : null),
                'service' => $shipping['service'] ?? ($transactionMethod === 'local_delivery' ? 'BreadLab Delivery' : null),
                'shipping_cost' => $shippingCost,
                'estimated_delivery' => $shipping['estimation'] ?? ($transactionMethod === 'local_delivery' ? 'Hari yang sama' : null),
                'delivery_address' => $validated['shipping_address'] ?? null,
            ]);

            $order->payment()->create([
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'midtrans_order_id' => $paymentMethod === 'midtrans'
                    ? 'MIDTRANS-' . now()->format('YmdHis') . '-' . auth()->id()
                    : null,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->qty,
                    'price' => $item->product->price,
                    'subtotal' => $item->subtotal(),
                ]);

                $item->product->decrement('stock', $item->qty);
            }

            $cart->items()->delete();

            return $order;
        });

        return redirect()->route('checkout.success', $order)->with('success', 'Pesanan berhasil dibuat.');
    }

    public function success(Order $order)
    {
        abort_unless((int) $order->customer?->user_id === auth()->id(), 403);

        return view('checkout.success', [
            'judul' => 'Pesanan Dibuat',
            'order' => $order->load('items.product', 'payment', 'shipment'),
        ]);
    }

    public function history()
    {
        $orders = Order::with('items.product', 'payment', 'shipment')
            ->where('customer_id', $this->customer()->id)
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
            'customer_id' => $this->customer()->id,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }

    private function cartWeight(Cart $cart): int
    {
        return max(1, (int) $cart->items->sum(function ($item) {
            $weight = (int) ($item->product->getAttribute('weight') ?: 250);

            return $weight * $item->qty;
        }));
    }
}
