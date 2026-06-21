<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class CashierReceiptController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['outlet', 'cashier', 'payment'])
            ->where('order_channel', 'cashier_pos')
            ->when(! auth()->user()?->hasAnyRole(['admin', 'super_admin']), fn ($query) => $query->where('cashier_id', auth()->id()))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cashier.receipts.index', [
            'title' => 'Receipts',
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeReceipt($order);

        return view('cashier.receipts.show', [
            'title' => 'Receipt ' . ($order->order_number ?: '#' . $order->id),
            'order' => $order->load(['items.product', 'payment', 'cashier', 'outlet']),
        ]);
    }

    public function print(Order $order)
    {
        $this->authorizeReceipt($order);

        return view('cashier.receipts.print', [
            'title' => 'Print Receipt ' . ($order->order_number ?: '#' . $order->id),
            'order' => $order->load(['items.product', 'payment', 'cashier', 'outlet']),
        ]);
    }

    private function authorizeReceipt(Order $order): void
    {
        abort_unless($order->order_channel === 'cashier_pos', 403);

        if (! auth()->user()?->hasAnyRole(['admin', 'super_admin'])) {
            abort_unless((int) $order->cashier_id === (int) auth()->id(), 403);
        }
    }
}
