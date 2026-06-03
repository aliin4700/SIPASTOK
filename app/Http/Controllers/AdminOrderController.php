<?php

namespace App\Http\Controllers;

use App\Models\CheckoutOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = CheckoutOrder::with('user')
            ->latest()
            ->paginate(15);

        return view('backend.orders.index', [
            'judul' => 'Pesanan',
            'orders' => $orders,
            'statuses' => CheckoutOrder::STATUSES,
        ]);
    }

    public function show(CheckoutOrder $order)
    {
        return view('backend.orders.show', [
            'judul' => 'Detail Pesanan',
            'order' => $order->load('user', 'items.product.category'),
            'statuses' => CheckoutOrder::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, CheckoutOrder $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(CheckoutOrder::STATUSES)],
        ]);

        $order->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
