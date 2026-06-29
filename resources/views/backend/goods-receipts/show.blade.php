@extends('backend.v_layouts.app')

@section('content')
@if($goodsReceipt->status === 'submitted')
    <div class="alert alert-warning">Apply stock akan menambah inventory stock melalui StockMovementService.</div>
@elseif($goodsReceipt->status === 'applied')
    <div class="alert alert-success">Goods receipt sudah diterapkan ke stock movement.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>No Receipt:</strong> {{ $goodsReceipt->receipt_number ?? '-' }}</div>
        <div><strong>PO:</strong> {{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}</div>
        <div><strong>Supplier:</strong> {{ $goodsReceipt->supplier->name ?? '-' }}</div>
        <div><strong>Outlet:</strong> {{ $goodsReceipt->outlet->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $goodsReceipt->receipt_date?->format('d/m/Y') }}</div>
        <div><strong>Received By:</strong> {{ $goodsReceipt->receivedBy->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $goodsReceipt->status }}</span></div>
        <div><strong>Applied By:</strong> {{ $goodsReceipt->appliedBy->name ?? '-' }}</div>
        <div><strong>Applied At:</strong> {{ $goodsReceipt->applied_at?->format('d/m/Y H:i') ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $goodsReceipt->notes ?? '-' }}</div>

        <div class="mt-3">
            @if($goodsReceipt->status === 'draft')
                <form method="post" action="{{ route('backend.goods-receipts.submit', $goodsReceipt) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-success">Submit</button>
                </form>
            @endif
            @if(in_array($goodsReceipt->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('backend.goods-receipts.cancel', $goodsReceipt) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-danger">Cancel</button>
                </form>
            @endif
            @if($goodsReceipt->status === 'submitted')
                <form method="post" action="{{ route('backend.goods-receipts.apply-stock', $goodsReceipt) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-primary">Apply Stock</button>
                </form>
            @endif
            <a href="{{ route('backend.goods-receipts.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6>Item Goods Receipt</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Terima</th>
                        <th>Unit</th>
                        <th>Unit Cost</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsReceipt->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->received_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit ?? $item->item->base_unit ?? '-' }}</td>
                            <td>{{ $item->unit_cost ? 'Rp ' . number_format((float) $item->unit_cost, 0, ',', '.') : '-' }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge badge-success">Applied</span>
                                @elseif($goodsReceipt->status === 'applied')
                                    <span class="badge badge-warning">No Movement</span>
                                @else
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
