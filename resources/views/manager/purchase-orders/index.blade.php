@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Purchase Order Monitoring</h4>
        <form method="get" class="row mb-3">
            <div class="col-md-4 mb-2">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nomor PO atau supplier">
            </div>
            <div class="col-md-3 mb-2">
                <select name="outlet_id" class="form-control">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((string) request('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <select name="status" class="form-control">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No PO</th>
                        <th>Supplier</th>
                        <th>Outlet</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $purchaseOrder)
                        <tr>
                            <td>{{ $purchaseOrder->po_number ?? '-' }}</td>
                            <td>{{ $purchaseOrder->supplier->name ?? '-' }}</td>
                            <td>{{ $purchaseOrder->outlet->name ?? '-' }}</td>
                            <td>{{ $purchaseOrder->order_date?->format('d/m/Y') }}</td>
                            <td><span class="badge bg-info">{{ $purchaseOrder->status }}</span></td>
                            <td>{{ $purchaseOrder->items->count() }}</td>
                            <td><a href="{{ route('manager.purchase-orders.show', $purchaseOrder) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada purchase order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $purchaseOrders->links() }}
    </div>
</div>
@endsection
