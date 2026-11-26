@extends('manager.layouts.app')

@section('content')
@if($materialRequest->status === 'issued')
    <div class="alert alert-success">Mutasi stok untuk dokumen Take Bahan / Barang Masuk ini sudah diterapkan melalui stock movement.</div>
@elseif($materialRequest->status === 'submitted')
    <div class="alert alert-warning">
        Dokumen ini berstatus <strong>SUBMITTED</strong>. Pilih apakah dokumen ini merupakan <strong>Penerimaan Barang Masuk / Restock Divisi (+ Tambah Stok)</strong> atau <strong>Pengeluaran Bahan (- Kurangi Stok)</strong>.
    </div>
@endif

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">Detail Take Bahan / Material Request: {{ $materialRequest->request_number ?? '-' }}</h4>
                <div class="text-muted small">Tanggal: {{ $materialRequest->request_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge {{ $materialRequest->status === 'issued' ? 'bg-success' : ($materialRequest->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info') }} text-uppercase fs-6">
                {{ $materialRequest->status }}
            </span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $materialRequest->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $materialRequest->division->name ?? '-' }}</div>
        <div><strong>Requester:</strong> {{ $materialRequest->requester->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $materialRequest->notes ?? '-' }}</div>

        @if($materialRequest->status === 'submitted')
            <div class="d-flex flex-wrap gap-2 mt-3">
                <form method="post" action="{{ route('manager.material-requests.issue-stock', $materialRequest) }}" onsubmit="return confirm('Tambahkan item ini sebagai Barang Masuk / Restock (+) ke stok divisi?');">
                    @csrf
                    <input type="hidden" name="mode" value="restock_in">
                    <button type="submit" class="btn btn-success fw-bold">
                        + Approve Barang Masuk / Restock Divisi (Tambah Stok)
                    </button>
                </form>

                <form method="post" action="{{ route('manager.material-requests.issue-stock', $materialRequest) }}" onsubmit="return confirm('Keluarkan item ini dari stok inventori (- Kurangi Stok)?');">
                    @csrf
                    <input type="hidden" name="mode" value="issue_out">
                    <button type="submit" class="btn btn-outline-primary fw-semibold">
                        &minus; Issue / Keluarkan Bahan (Kurangi Stok)
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-3 fw-bold">Rincian Item</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty Request</th>
                        <th class="text-end">Qty Diproses</th>
                        <th>Unit</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialRequest->items as $item)
                        @php
                            $movement = $movementsByItemId[$item->id] ?? null;
                            $issueQty = (float) ($item->issued_qty ?: ($item->approved_qty ?: $item->requested_qty));
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $item->item->name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $item->requested_qty, 2, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($issueQty, 2, ',', '.') }}</td>
                            <td>{{ strtoupper($item->unit) }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge bg-success">{{ $movement->movement_type === 'transfer_in' ? 'Restock In (+)' : 'Issued Out (-)' }}</span>
                                @elseif($materialRequest->status === 'issued')
                                    <span class="badge bg-warning text-dark">No Movement</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('manager.material-requests.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
