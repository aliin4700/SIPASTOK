<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SoldProductReportController extends Controller
{
    private const TRANSACTION_METHODS = [
        'pickup',
        'local_delivery',
        'shipping',
    ];

    public function index()
    {
        return view('backend.reports.sold-products.index', [
            'judul' => 'Laporan Produk Terjual',
            'orderStatuses' => Order::STATUSES,
            'transactionMethods' => self::TRANSACTION_METHODS,
        ]);
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal'],
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'transaction_method' => ['nullable', Rule::in(self::TRANSACTION_METHODS)],
        ], [
            'tanggal_akhir.after_or_equal' => 'Tanggal Akhir harus lebih besar atau sama dengan Tanggal Awal.',
        ]);

        $items = OrderItem::with(['order', 'product'])
            ->whereHas('order', function ($query) use ($validated) {
                $query
                    // Laporan produk terjual memakai semua order non-cancelled agar kompatibel dengan status lama.
                    ->where('status', '!=', 'cancelled')
                    ->when($validated['tanggal_awal'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                    ->when($validated['tanggal_akhir'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
                    ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                    ->when($validated['transaction_method'] ?? null, fn ($query, $method) => $query->where('transaction_method', $method));
            })
            ->get();

        return view('backend.reports.sold-products.print', [
            'judul' => 'Laporan Produk Terjual',
            'tanggalAwal' => $validated['tanggal_awal'] ?? null,
            'tanggalAkhir' => $validated['tanggal_akhir'] ?? null,
            'status' => $validated['status'] ?? null,
            'transactionMethod' => $validated['transaction_method'] ?? null,
            'soldProducts' => $this->summarizeSoldProducts($items),
        ]);
    }

    private function summarizeSoldProducts(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (OrderItem $item) => $item->product_id ?: $item->product_name)
            ->map(function (Collection $items) {
                $firstItem = $items->first();
                $totalSold = (int) $items->sum('quantity');
                $totalRevenue = (float) $items->sum('subtotal');

                return (object) [
                    'product_code' => $firstItem->product?->slug ?: $firstItem->product_id,
                    'product_name' => $firstItem->product_name,
                    'total_sold' => $totalSold,
                    'average_price' => $totalSold > 0 ? $totalRevenue / $totalSold : 0,
                    'total_revenue' => $totalRevenue,
                    'transaction_count' => $items->pluck('order_id')->unique()->count(),
                    'channels' => $items
                        ->pluck('order.transaction_method')
                        ->filter()
                        ->unique()
                        ->map(fn ($method) => str_replace('_', ' ', $method))
                        ->implode(', '),
                ];
            })
            ->sortByDesc('total_sold')
            ->values();
    }
}
