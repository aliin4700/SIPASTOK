<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Monitoring order dari sisi admin.
 *
 * Update status order dipisahkan dari payment verification agar status
 * operasional dan status pembayaran tidak saling menimpa.
 */
class AdminOrderController extends Controller
{
    /**
     * Menampilkan daftar order untuk admin.
     */
    public function index()
    {
        $orders = Order::with('customer.user', 'payment', 'shipment')
            ->latest()
            ->paginate(15);

        return view('backend.v_order.index', [
            'judul' => 'Pesanan',
            'orders' => $orders,
            'statuses' => Order::STATUSES,
        ]);
    }

    /**
     * Menampilkan detail order untuk admin.
     */
    public function show(Order $order)
    {
        return view('backend.v_order.show', [
            'judul' => 'Detail Pesanan',
            'order' => $order->load('customer.user', 'items.product.category', 'payment', 'shipment', 'conversation'),
            'statuses' => Order::STATUSES,
        ]);
    }

    /**
     * Mengubah status operasional order.
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Form ini hanya mengubah orders.status, bukan payments.payment_status.
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $order->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('backend.order.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    /**
     * Mengubah data tracking pengiriman courier.
     */
    public function updateTracking(Request $request, Order $order)
    {
        // Tracking hanya relevan untuk pengiriman courier, bukan pickup/local delivery.
        if (($order->shipment?->shipping_type ?? $order->transaction_method) !== 'shipping') {
            return redirect()
                ->route('backend.order.show', $order)
                ->with('success', 'Tracking hanya digunakan untuk shipping courier.');
        }

        $validated = $request->validate([
            'courier' => ['nullable', 'string', 'max:50'],
            'service' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $order->shipment()->updateOrCreate([
            'order_id' => $order->id,
        ], [
            'shipping_type' => $order->transaction_method,
            'courier' => $validated['courier'] ?? null,
            'service' => $validated['service'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'shipping_cost' => $order->shipment?->shipping_cost ?? $order->shipping_cost ?? 0,
            'estimated_delivery' => $order->shipment?->estimated_delivery,
            'delivery_address' => $order->shipment?->delivery_address,
            'delivery_note' => $order->shipment?->delivery_note,
        ]);

        return redirect()
            ->route('backend.order.show', $order)
            ->with('success', 'Tracking pengiriman berhasil diperbarui.');
    }
}
