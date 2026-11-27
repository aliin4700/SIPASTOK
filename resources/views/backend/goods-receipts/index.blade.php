@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5 class="card-title mb-2">{{ $judul }}</h5>
            <a href="{{ route('backend.goods-receipts.create') }}" class="btn btn-primary btn-sm mb-2">Tambah Goods Receipt</a>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <form method="get" class="border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="No receipt, supplier, atau PO">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Outlet</label>
                    <select name="outlet_id" class="form-control">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((string) request('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end mb-2">
                    <button class="btn btn-primary mr-1">Filter</button>
                    <a href="{{ route('backend.goods-receipts.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No Receipt</th>
                        <th>PO</th>
                        <th>Supplier</th>
                        <th>Outlet</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th class="text-right">Total Qty</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($goodsReceipts as $goodsReceipt)
                        <tr>
                            <td>{{ $goodsReceipt->receipt_number ?? '-' }}</td>
                            <td>{{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}</td>
                            <td>{{ $goodsReceipt->supplier->name ?? '-' }}</td>
                            <td>{{ $goodsReceipt->outlet->name ?? '-' }}</td>
                            <td>{{ $goodsReceipt->receipt_date?->format('d/m/Y') }}</td>
                            <td><span class="badge badge-info">{{ $goodsReceipt->status }}</span></td>
                            <td>{{ $goodsReceipt->items_count }}</td>
                            <td class="text-right">{{ number_format((float) ($goodsReceipt->items_sum_received_qty ?? 0), 2, ',', '.') }}</td>
                            <td><a href="{{ route('backend.goods-receipts.show', $goodsReceipt) }}" class="btn btn-info btn-sm">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada goods receipt.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $goodsReceipts->links() }}
    </div>
</div>
@endsection
