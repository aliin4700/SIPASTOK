<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard ringkas admin berdasarkan tabel final BreadLab.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard admin berisi ringkasan order, payment, revenue, dan chat.
     */
    public function index()
    {
        // Trend pendapatan memakai 14 hari terakhir agar grafik tetap ringan.
        $startDate = now()->subDays(13)->startOfDay();

        $orderStatusCounts = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $paymentStatusCounts = Payment::query()
            ->select('payment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status');

        $revenueRows = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.payment_status', 'paid')
            ->where('payments.updated_at', '>=', $startDate)
            ->selectRaw('DATE(payments.updated_at) as revenue_date, SUM(orders.total_price) as total')
            ->groupBy('revenue_date')
            ->orderBy('revenue_date')
            ->pluck('total', 'revenue_date');

        // Isi hari tanpa penjualan dengan nol supaya Chart.js tidak punya tanggal bolong.
        $revenueTrend = collect(CarbonPeriod::create($startDate, now()->startOfDay()))
            ->map(function ($date) use ($revenueRows) {
                $key = $date->format('Y-m-d');

                return [
                    'label' => $date->format('d M'),
                    'total' => (float) ($revenueRows[$key] ?? 0),
                ];
            });

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->where('payments.payment_status', 'paid')
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as total_quantity')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // Tabel dashboard hanya mengambil data terbaru, bukan laporan lengkap.
        $latestOrders = Order::with('customer.user', 'payment')
            ->latest()
            ->limit(8)
            ->get();

        $latestChats = Conversation::with('order', 'customer.user', 'latestMessage.sender')
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_type', 'customer')
                    ->where('is_read', false),
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('backend.v_dashboard.index', [
            'judul' => 'Dashboard',
            'cards' => [
                'totalOrders' => Order::count(),
                'totalRevenue' => (float) Payment::query()
                    ->join('orders', 'orders.id', '=', 'payments.order_id')
                    ->where('payments.payment_status', 'paid')
                    ->sum('orders.total_price'),
                'pendingPayments' => Payment::whereIn('payment_status', ['pending', 'waiting_confirmation'])->count(),
                'totalCustomers' => Customer::count(),
                'totalProducts' => Product::count(),
            ],
            'charts' => [
                'orderStatus' => [
                    'labels' => $orderStatusCounts->keys()->map(fn ($status) => ucwords(str_replace('_', ' ', $status)))->values(),
                    'data' => $orderStatusCounts->values(),
                ],
                'paymentStatus' => [
                    'labels' => $paymentStatusCounts->keys()->map(fn ($status) => ucwords(str_replace('_', ' ', $status)))->values(),
                    'data' => $paymentStatusCounts->values(),
                ],
                'revenueTrend' => [
                    'labels' => $revenueTrend->pluck('label')->values(),
                    'data' => $revenueTrend->pluck('total')->values(),
                ],
                'topProducts' => [
                    'labels' => $topProducts->pluck('product_name')->values(),
                    'data' => $topProducts->pluck('total_quantity')->map(fn ($value) => (int) $value)->values(),
                ],
            ],
            'latestOrders' => $latestOrders,
            'latestChats' => $latestChats,
        ]);
    }
}
