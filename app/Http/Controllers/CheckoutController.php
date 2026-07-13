<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\RajaOngkirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Mengubah cart customer menjadi order final.
 *
 * Controller ini menjaga pembuatan order, order item, payment, dan shipment
 * tetap konsisten dalam satu database transaction.
 */
class CheckoutController extends Controller
{
    public function __construct(private RajaOngkirService $rajaOngkir)
    {
    }

    /**
     * Menampilkan halaman checkout dan ringkasan cart customer.
     */
    public function index()
    {
        // Checkout selalu dimulai dari cart aktif customer.
        $cart = $this->cart()->load('items.product.category');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        return view('v_checkout.index', [
            'judul' => 'Checkout',
            'cart' => $cart,
            'subtotal' => $this->subtotal($cart),
            'totalWeight' => $this->totalWeight($cart),
            'localDeliveryFee' => $this->localDeliveryFee(),
            'couriers' => RajaOngkirService::COURIERS,
            'customerAddresses' => $this->customer()->addresses()->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    /**
     * Memproses checkout menjadi order, order item, payment, dan shipment.
     */
    public function process(Request $request)
    {
        // Field checkout dibuat kondisional karena pickup, local delivery,
        // dan shipping memiliki kebutuhan data yang berbeda.
        $validated = $request->validate([
            'transaction_method' => ['required', Rule::in(['pickup', 'local_delivery', 'shipping'])],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'midtrans'])],
            'customer_address_id' => ['nullable', 'required_if:transaction_method,shipping', 'integer', 'exists:customer_addresses,id'],
            'address' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:20'],
            'province_id' => ['nullable', 'string', 'max:50'],
            'province_name' => ['nullable', 'string', 'max:255'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'delivery_address' => ['nullable', 'required_if:transaction_method,local_delivery', 'string', 'max:1000'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'destination' => ['nullable', 'required_if:transaction_method,shipping', 'integer'],
            'courier' => ['nullable', 'required_if:transaction_method,shipping', 'string', Rule::in(array_keys(RajaOngkirService::COURIERS))],
            'service' => ['nullable', 'required_if:transaction_method,shipping', 'string', 'max:100'],
            'shipping_cost' => ['exclude_unless:transaction_method,shipping', 'required', 'numeric', 'min:1'],
            'estimated_delivery' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validated['payment_method'] === 'cash' && ! in_array($validated['transaction_method'], ['pickup', 'local_delivery'], true)) {
            return back()
                ->withInput()
                ->with('error', 'Cash hanya tersedia untuk pickup dan local delivery.');
        }

        $selectedAddress = null;

        if ($validated['transaction_method'] === 'shipping') {
            // Shipping memakai address book milik customer agar ongkir tidak bergantung
            // pada alamat bebas yang bisa dimanipulasi dari frontend.
            $selectedAddress = $this->customer()->addresses()->whereKey($validated['customer_address_id'])->first();

            if (! $selectedAddress) {
                return back()
                    ->withInput()
                    ->with('error', 'Alamat pengiriman tidak ditemukan atau bukan milik akun Anda.');
            }

            if (blank($selectedAddress->city_id)) {
                return back()
                    ->withInput()
                    ->with('error', 'Alamat terpilih belum memiliki ID kota RajaOngkir untuk cek ongkir.');
            }

            if ((string) config('services.rajaongkir.mode', 'starter') === 'komerce' && blank($selectedAddress->district_id)) {
                return back()
                    ->withInput()
                    ->with('error', 'Alamat belum memiliki kecamatan. Edit alamat terlebih dahulu.');
            }

            $validated['destination'] = (int) ((string) config('services.rajaongkir.mode', 'starter') === 'komerce'
                ? $selectedAddress->district_id
                : $selectedAddress->city_id);
            $validated['address'] = $selectedAddress->snapshotAddress();
            $validated['postal_code'] = $selectedAddress->postal_code;
            $validated['province_id'] = $selectedAddress->province_id;
            $validated['province_name'] = $selectedAddress->province_name;
            $validated['city_name'] = $selectedAddress->city_name;
        }

        $cart = $this->cart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        // Cek stok awal memberi pesan cepat sebelum masuk transaction.
        // Stok tetap dicek ulang dengan lockForUpdate saat order dibuat.
        foreach ($cart->items as $item) {
            if ((int) $item->quantity > (int) $item->product->stock) {
                return redirect()->route('cart.index')
                    ->with('error', 'Stok ' . $item->product->name . ' tidak mencukupi.');
            }
        }

        try {
            $shipmentOption = $this->shipmentOption($validated, $cart);

            $order = DB::transaction(function () use ($cart, $validated, $shipmentOption, $selectedAddress) {
                // Reload cart di dalam transaction agar produk dan harga yang dipakai masih segar.
                $cart->load('items.product');
                $subtotal = $this->subtotal($cart);
                $totalWeight = $this->totalWeight($cart);
                $shippingCost = $shipmentOption['shipping_cost'];
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
                    'address' => $validated['transaction_method'] === 'shipping'
                        ? ($validated['address'] ?? null)
                        : ($validated['delivery_address'] ?? null),
                    'postal_code' => $validated['postal_code'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($cart->items as $item) {
                    // Lock produk mencegah dua checkout bersamaan memakai stok yang sama.
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
                    'courier' => $shipmentOption['courier'],
                    'service' => $shipmentOption['service'],
                    'tracking_number' => null,
                    'shipping_cost' => $shippingCost,
                    'estimated_delivery' => $shipmentOption['estimated_delivery'],
                    'delivery_latitude' => match ($validated['transaction_method']) {
                        'local_delivery' => $validated['delivery_latitude'] ?? null,
                        'shipping' => $selectedAddress?->latitude,
                        default => null,
                    },
                    'delivery_longitude' => match ($validated['transaction_method']) {
                        'local_delivery' => $validated['delivery_longitude'] ?? null,
                        'shipping' => $selectedAddress?->longitude,
                        default => null,
                    },
                    'delivery_address' => match ($validated['transaction_method']) {
                        'local_delivery' => $validated['delivery_address'] ?? null,
                        'shipping' => $validated['address'] ?? null,
                        default => null,
                    },
                    'delivery_note' => match ($validated['transaction_method']) {
                        'local_delivery' => $validated['delivery_note'] ?? null,
                        'shipping' => $selectedAddress
                            ? trim(($selectedAddress->receiver_name . ' - ' . $selectedAddress->receiver_phone . ' ' . ($selectedAddress->label ? '(' . $selectedAddress->label . ')' : '')))
                            : null,
                        default => null,
                    },
                    'delivery_distance' => null,
                ]);

                // Cart dikosongkan hanya setelah order, payment, shipment, dan item berhasil dibuat.
                $cart->items()->delete();

                return $order;
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
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
        $weight = (int) $cart->items->sum(fn (CartItem $item) => (int) ($item->product->weight ?: $this->defaultWeight()) * $item->quantity);

        return max($this->defaultWeight(), $weight);
    }

    /**
     * Menentukan data ongkir berdasarkan metode transaksi.
     */
    private function shipmentOption(array $validated, Cart $cart): array
    {
        if ($validated['transaction_method'] === 'pickup') {
            // Pickup tidak membutuhkan ongkir, kurir, atau estimasi pengiriman.
            return [
                'shipping_cost' => 0,
                'courier' => null,
                'service' => null,
                'estimated_delivery' => null,
            ];
        }

        if ($validated['transaction_method'] === 'local_delivery') {
            // Local delivery memakai fee internal dari konfigurasi, bukan input frontend.
            return [
                'shipping_cost' => $this->localDeliveryFee(),
                'courier' => null,
                'service' => null,
                'estimated_delivery' => null,
            ];
        }

        if (config('services.rajaongkir.key') && (string) config('services.rajaongkir.mode', 'starter') !== 'komerce') {
            // Mode starter bisa divalidasi ulang ke RajaOngkirService sebelum order dibuat.
            $service = $this->rajaOngkir->findService(
                $validated['destination'],
                $validated['courier'],
                $validated['service'],
                $this->totalWeight($cart)
            );

            if (! $service) {
                throw new RuntimeException('Service RajaOngkir yang dipilih tidak valid.');
            }

            return [
                'shipping_cost' => (float) $service['cost'],
                'courier' => $service['courier'],
                'service' => $service['service'],
                'estimated_delivery' => $service['estimation'],
            ];
        }

        // Mode Komerce menggunakan service yang sudah dipilih di UI checkout.
        return [
            'shipping_cost' => (float) ($validated['shipping_cost'] ?? 0),
            'courier' => $validated['courier'] ?? null,
            'service' => $validated['service'] ?? null,
            'estimated_delivery' => $validated['estimated_delivery'] ?? null,
        ];
    }

    private function localDeliveryFee(): float
    {
        return (float) config('services.local_delivery.fee', 10000);
    }

    private function defaultWeight(): int
    {
        return max(1, (int) config('services.rajaongkir.default_weight', 1000));
    }

    /**
     * Membuat nomor pesanan unik.
     */
    private function orderNumber(): string
    {
        // Format pendek ini cukup aman untuk transaksi harian dan tetap mudah dibaca admin.
        do {
            $number = 'BRD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
