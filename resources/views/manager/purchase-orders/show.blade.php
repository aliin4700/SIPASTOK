@extends('manager.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3">Detail Purchase Order</h4>
        <div><strong>No PO:</strong> {{ $purchaseOrder->po_number ?? '-' }}</div>
        <div><strong>Supplier:</strong> {{ $purchaseOrder->supplier->name ?? '-' }}</div>
        <div><strong>Outlet:</strong> {{ $purchaseOrder->outlet->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $purchaseOrder->order_date?->format('d/m/Y') }}</div>
        <div><strong>Status:</strong> <span class="badge bg-info">{{ $purchaseOrder->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $purchaseOrder->notes ?? '-' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Item</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Order</th>
                        <th>Qty Received</th>
                        <th>Unit</th>
                        <th>Unit Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->ordered_qty, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->received_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit ?? '-' }}</td>
                            <td>{{ $item->unit_cost ? 'Rp ' . number_format((float) $item->unit_cost, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('manager.purchase-orders.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
