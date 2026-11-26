@extends('manager.layouts.app')

@section('content')
@if($goodsReceipt->status === 'applied')
    <div class="alert alert-success">Penerimaan barang (Goods Receipt) ini sudah ditambahkan ke stok inventori outlet (`purchase_in`).</div>
@elseif($goodsReceipt->status === 'submitted')
    <div class="alert alert-warning">Goods Receipt ini berstatus <strong>SUBMITTED</strong>. Klik tombol <strong>Approve &amp; Tambah Stok Barang Masuk</strong> di bawah untuk memperbarui stok dan harga beli terakhir.</div>
@endif

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">Detail Goods Receipt: {{ $goodsReceipt->receipt_number ?? '-' }}</h4>
                <div class="text-muted small">Tanggal Terima: {{ $goodsReceipt->receipt_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge {{ $goodsReceipt->status === 'applied' ? 'bg-success' : ($goodsReceipt->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info') }} text-uppercase fs-6">
                {{ $goodsReceipt->status }}
            </span>
        </div>
        <hr>
        <div><strong>PO:</strong> {{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}</div>
        <div><strong>Supplier:</strong> {{ $goodsReceipt->supplier->name ?? '-' }}</div>
        <div><strong>Outlet:</strong> {{ $goodsReceipt->outlet->name ?? '-' }}</div>
        <div><strong>Applied By:</strong> {{ $goodsReceipt->appliedBy->name ?? '-' }}</div>
        <div><strong>Applied At:</strong> {{ $goodsReceipt->applied_at?->format('d/m/Y H:i') ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $goodsReceipt->notes ?? '-' }}</div>

        @if($goodsReceipt->status === 'submitted')
            <form method="post" action="{{ route('manager.goods-receipts.apply-stock', $goodsReceipt) }}" class="mt-3" onsubmit="return confirm('Tambahkan barang yang diterima ini ke saldo stok outlet sekarang?');">
                @csrf
                <button type="submit" class="btn btn-success fw-bold">
                    &#10003; Approve &amp; Tambah Stok Barang Masuk (Apply Stock)
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-3 fw-bold">Item Penerimaan</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty Terima</th>
                        <th>Unit</th>
                        <th class="text-end">Unit Cost (Rp)</th>
                        <th>Movement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsReceipt->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td class="fw-semibold">{{ $item->item->name ?? '-' }}</td>
                            <td class="text-end fw-bold text-success">+{{ number_format((float) $item->received_qty, 2, ',', '.') }}</td>
                            <td>{{ strtoupper($item->unit ?? '-') }}</td>
                            <td class="text-end">{{ $item->unit_cost ? 'Rp ' . number_format((float) $item->unit_cost, 0, ',', '.') : '-' }}</td>
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
