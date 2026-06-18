<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;

class OrderController extends Controller
{
    public function history()
    {
        $orders = Order::with('payment', 'shipment', 'items.product')
            ->where('customer_id', $this->customer()->id)
            ->latest()
            ->paginate(10);

        return view('v_order.history', [
            'judul' => 'Riwayat Pesanan',
            'orders' => $orders,
        ]);
    }

    public function detail(Order $order)
    {
        abort_unless((int) $order->customer_id === (int) $this->customer()->id, 403);

        return view('v_order.detail', [
            'judul' => 'Detail Pesanan',
            'order' => $order->load('items.product.category', 'payment', 'shipment', 'customer.user'),
        ]);
    }

    private function customer(): Customer
    {
        return Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }
}
