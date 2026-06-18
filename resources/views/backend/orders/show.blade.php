@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel {
        background: #fff;
        border: 1px solid #f0e4d3;
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(139, 69, 19, .08);
        padding: 24px;
    }

    .status-badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 10px;
        text-transform: capitalize;
    }

    .status-pending, .status-waiting_payment { background: #fff3cd; color: #8a6400; }
    .status-paid, .status-processing, .status-ready_for_pickup, .status-delivering, .status-shipped, .status-completed { background: #d1e7dd; color: #0f5132; }
    .status-cancelled { background: #f8d7da; color: #842029; }
    .order-product-img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; }
</style>

<div class="breadlab-admin-panel mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-decoration-none">&larr; Kembali ke Pesanan</a>
            <h3 class="mb-1 mt-2">Detail Pesanan #{{ $order->id }}</h3>
            <p class="text-muted mb-0">{{ $order->created_at->format('d M Y H:i') }}</p>
        </div>
        <div class="text-md-end">
            <span class="status-badge status-{{ $order->paymentStatus() }}">Payment: {{ $order->paymentStatus() }}</span>
            <span class="status-badge status-{{ $order->normalizedStatus() }} ms-2">Order: {{ $order->normalizedStatus() }}</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <h4 class="h5 mb-3">Informasi Customer</h4>
            <dl class="row">
                <dt class="col-sm-4">Nama</dt>
                <dd class="col-sm-8">{{ $order->user->nama ?? $order->user->name ?? '-' }}</dd>
                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8">{{ $order->user->email ?? '-' }}</dd>
                <dt class="col-sm-4">Metode Transaksi</dt>
                <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $order->transaction_method ?? 'shipping')) }}</dd>
                <dt class="col-sm-4">Alamat</dt>
                <dd class="col-sm-8">{{ $order->shipping_address ?? '-' }}</dd>
                <dt class="col-sm-4">Provinsi</dt>
                <dd class="col-sm-8">{{ $order->shipping_province ?? '-' }}</dd>
                <dt class="col-sm-4">Kota</dt>
                <dd class="col-sm-8">{{ $order->shipping_city ?? '-' }}</dd>
                <dt class="col-sm-4">Kode Pos</dt>
                <dd class="col-sm-8">{{ $order->shipping_postal_code ?? '-' }}</dd>
                <dt class="col-sm-4">Metode Bayar</dt>
                <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $order->payment_method ?? '-')) }}</dd>
                <dt class="col-sm-4">Referensi</dt>
                <dd class="col-sm-8">{{ $order->payment_reference ?? '-' }}</dd>
            </dl>
        </div>

        <div class="col-lg-5">
            <h4 class="h5 mb-3">Update Status</h4>
            <form action="{{ route('admin.orders.update-status', $order) }}" method="post">
                @csrf
                @method('patch')
                <div class="mb-3">
                    <label for="status" class="form-label">Order Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                        @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected($order->normalizedStatus() === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Simpan Status</button>
            </form>
        </div>
    </div>
</div>

<div class="breadlab-admin-panel mb-4">
    <h4 class="h5 mb-3">Informasi Pengiriman</h4>
    <div class="row">
        <div class="col-lg-7">
            <dl class="row mb-0">
                <dt class="col-sm-4">Alamat Kirim</dt>
                <dd class="col-sm-8">{{ $order->shipping_address ?? '-' }}</dd>
                <dt class="col-sm-4">Tujuan</dt>
                <dd class="col-sm-8">{{ $order->shipping_city ?? '-' }}, {{ $order->shipping_province ?? '-' }}</dd>
                <dt class="col-sm-4">Kode Pos</dt>
                <dd class="col-sm-8">{{ $order->shipping_postal_code ?? '-' }}</dd>
                <dt class="col-sm-4">Berat Total</dt>
                <dd class="col-sm-8">{{ number_format($order->shipping_total_weight ?? 0, 0, ',', '.') }} gram</dd>
            </dl>
        </div>
        <div class="col-lg-5">
            <dl class="row mb-0">
                <dt class="col-sm-5">Kurir</dt>
                <dd class="col-sm-7">{{ $order->shipping_courier ?? '-' }}</dd>
                <dt class="col-sm-5">Layanan</dt>
                <dd class="col-sm-7">{{ $order->shipping_service ?? '-' }}</dd>
                <dt class="col-sm-5">Ongkir</dt>
                <dd class="col-sm-7">Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</dd>
                <dt class="col-sm-5">Estimasi</dt>
                <dd class="col-sm-7">{{ $order->shipping_estimation ? $order->shipping_estimation . ' hari' : '-' }}</dd>
            </dl>
        </div>
    </div>
</div>

<div class="breadlab-admin-panel">
    <h4 class="h5 mb-3">Item Pesanan</h4>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            @if($item->product)
                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="order-product-img">
                            @endif
                            <span class="fw-semibold">{{ $item->product->name ?? 'Produk tidak tersedia' }}</span>
                        </div>
                    </td>
                    <td>{{ $item->product->category->nama_kategori ?? '-' }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->price * $item->qty, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php($productTotal = $order->items->sum(fn ($item) => $item->price * $item->qty))
                <tr>
                    <th colspan="4" class="text-end">Subtotal Produk</th>
                    <th>Rp {{ number_format($productTotal, 0, ',', '.') }}</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">Ongkir</th>
                    <th>Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">Total Bayar</th>
                    <th>Rp {{ number_format($order->total_price, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
