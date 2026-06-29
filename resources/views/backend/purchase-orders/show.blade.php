@extends('backend.v_layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>No PO:</strong> {{ $purchaseOrder->po_number ?? '-' }}</div>
        <div><strong>Supplier:</strong> {{ $purchaseOrder->supplier->name ?? '-' }}</div>
        <div><strong>Outlet:</strong> {{ $purchaseOrder->outlet->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $purchaseOrder->order_date?->format('d/m/Y') }}</div>
        <div><strong>Estimasi:</strong> {{ $purchaseOrder->expected_date?->format('d/m/Y') ?? '-' }}</div>
        <div><strong>Ordered By:</strong> {{ $purchaseOrder->orderedBy->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $purchaseOrder->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $purchaseOrder->notes ?? '-' }}</div>

        <div class="mt-3">
            @if($purchaseOrder->status === 'draft')
                <form method="post" action="{{ route('backend.purchase-orders.submit', $purchaseOrder) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-success">Submit</button>
                </form>
            @endif
            @if(in_array($purchaseOrder->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('backend.purchase-orders.cancel', $purchaseOrder) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-danger">Cancel</button>
                </form>
            @endif
            @if($purchaseOrder->status === 'submitted')
                <a href="{{ route('backend.goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn btn-primary">Buat Goods Receipt</a>
            @endif
            <a href="{{ route('backend.purchase-orders.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6>Item PO</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Order</th>
                        <th>Qty Received</th>
                        <th>Unit</th>
                        <th>Unit Cost</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->ordered_qty, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->received_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit ?? $item->item->base_unit ?? '-' }}</td>
                            <td>{{ $item->unit_cost ? 'Rp ' . number_format((float) $item->unit_cost, 0, ',', '.') : '-' }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
