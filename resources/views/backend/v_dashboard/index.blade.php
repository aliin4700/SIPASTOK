@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); padding: 24px; }
    .summary-card { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); padding: 20px; height: 100%; }
    .summary-label { color: #6c757d; font-size: 13px; font-weight: 600; text-transform: uppercase; }
    .summary-value { color: #24160f; font-size: 26px; font-weight: 700; margin-top: 8px; }
    .chart-box { height: 280px; position: relative; }
    .status-badge { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 700; padding: 6px 10px; text-transform: capitalize; }
    .status-pending, .status-waiting_payment, .status-waiting_confirmation { background: #fff3cd; color: #8a6400; }
    .status-paid, .status-processing, .status-ready_for_pickup, .status-delivering, .status-shipped, .status-completed { background: #d1e7dd; color: #0f5132; }
    .status-cancelled, .status-failed, .status-rejected, .status-expired { background: #f8d7da; color: #842029; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1">Dashboard</h3>
        <p class="text-muted mb-0">Ringkasan operasional BreadLab dari data order, payment, produk, dan chat.</p>
    </div>
</div>

{{-- Card statistik dashboard admin. --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="summary-card">
            <div class="summary-label">Total Pesanan</div>
            <div class="summary-value">{{ number_format($cards['totalOrders'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card">
            <div class="summary-label">Pendapatan Paid</div>
            <div class="summary-value">Rp {{ number_format($cards['totalRevenue'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card">
            <div class="summary-label">Payment Pending</div>
            <div class="summary-value">{{ number_format($cards['pendingPayments'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card">
            <div class="summary-label">Customer</div>
            <div class="summary-value">{{ number_format($cards['totalCustomers'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card">
            <div class="summary-label">Produk</div>
            <div class="summary-value">{{ number_format($cards['totalProducts'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- Grafik status order, status pembayaran, pendapatan, dan produk terlaris. --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="breadlab-admin-panel">
            <h4 class="h5 mb-3">Order Status</h4>
            <div class="chart-box"><canvas id="orderStatusChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="breadlab-admin-panel">
            <h4 class="h5 mb-3">Payment Status</h4>
            <div class="chart-box"><canvas id="paymentStatusChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="breadlab-admin-panel">
            <h4 class="h5 mb-3">Revenue Trend</h4>
            <div class="chart-box"><canvas id="revenueTrendChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="breadlab-admin-panel">
            <h4 class="h5 mb-3">Top Product</h4>
            <div class="chart-box"><canvas id="topProductChart"></canvas></div>
        </div>
    </div>
</div>

{{-- Data terbaru untuk tindak lanjut admin. --}}
<div class="row g-4">
    <div class="col-xl-8">
        <div class="breadlab-admin-panel">
            <h4 class="h5 mb-3">Latest Orders</h4>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestOrders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->customer->user->name ?? '-' }}</td>
                            <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                            <td><span class="status-badge status-{{ $order->paymentStatus() }}">{{ str_replace('_', ' ', $order->paymentStatus()) }}</span></td>
                            <td><span class="status-badge status-{{ $order->normalizedStatus() }}">{{ str_replace('_', ' ', $order->normalizedStatus()) }}</span></td>
                            <td>{{ $order->created_at->format('d M Y') }}</td>
                            <td><a href="{{ route('backend.order.show', $order) }}" class="btn btn-primary btn-sm">Detail</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Belum ada pesanan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="breadlab-admin-panel">
            <h4 class="h5 mb-3">Latest Chats</h4>
            @forelse($latestChats as $conversation)
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">{{ $conversation->customer->user->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $conversation->order->order_number ?? '-' }}</div>
                    </div>
                    <span class="badge bg-warning text-dark">{{ $conversation->unread_count }} unread</span>
                </div>
                <div class="text-muted small mt-2">{{ \Illuminate\Support\Str::limit($conversation->latestMessage->message ?? 'Belum ada pesan.', 70) }}</div>
                <a href="{{ route('backend.chat.show', $conversation) }}" class="btn btn-outline-primary btn-sm mt-2">Buka Chat</a>
            </div>
            @empty
            <p class="text-muted mb-0">Belum ada chat.</p>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const charts = @json($charts);
    const palette = ['#8B4513', '#D2691E', '#2962ff', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#6c757d'];

    // Helper kecil agar konfigurasi Chart.js dashboard tidak berulang.
    function makeChart(id, type, labels, data, options = {}) {
        const element = document.getElementById(id);
        if (!element) {
            return;
        }

        new Chart(element, {
            type,
            data: {
                labels,
                datasets: [{
                    data,
                    label: options.label || '',
                    backgroundColor: type === 'line' ? 'rgba(139, 69, 19, .16)' : palette,
                    borderColor: '#8B4513',
                    borderWidth: 2,
                    tension: .35,
                    fill: type === 'line',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type !== 'bar' && type !== 'line' },
                },
                scales: type === 'doughnut' ? {} : {
                    y: { beginAtZero: true },
                },
            },
        });
    }

    makeChart('orderStatusChart', 'doughnut', charts.orderStatus.labels, charts.orderStatus.data);
    makeChart('paymentStatusChart', 'doughnut', charts.paymentStatus.labels, charts.paymentStatus.data);
    makeChart('revenueTrendChart', 'line', charts.revenueTrend.labels, charts.revenueTrend.data, { label: 'Revenue' });
    makeChart('topProductChart', 'bar', charts.topProducts.labels, charts.topProducts.data, { label: 'Terjual' });
</script>
@endsection
