@extends('manager.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3">Detail Goods Receipt</h4>
        <div><strong>No Receipt:</strong> {{ $goodsReceipt->receipt_number ?? '-' }}</div>
        <div><strong>PO:</strong> {{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}</div>
        <div><strong>Supplier:</strong> {{ $goodsReceipt->supplier->name ?? '-' }}</div>
        <div><strong>Outlet:</strong> {{ $goodsReceipt->outlet->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $goodsReceipt->receipt_date?->format('d/m/Y') }}</div>
        <div><strong>Status:</strong> <span class="badge bg-info">{{ $goodsReceipt->status }}</span></div>
        <div><strong>Applied By:</strong> {{ $goodsReceipt->appliedBy->name ?? '-' }}</div>
        <div><strong>Applied At:</strong> {{ $goodsReceipt->applied_at?->format('d/m/Y H:i') ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $goodsReceipt->notes ?? '-' }}</div>
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
                        <th>Qty Terima</th>
                        <th>Unit</th>
                        <th>Unit Cost</th>
                        <th>Movement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsReceipt->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->received_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit ?? '-' }}</td>
                            <td>{{ $item->unit_cost ? 'Rp ' . number_format((float) $item->unit_cost, 0, ',', '.') : '-' }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge bg-success">Applied</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('manager.goods-receipts.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
