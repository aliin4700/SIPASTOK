@extends('backend.v_layouts.app')

@section('content')
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $isProcessed = in_array($materialRequest->status, ['approved', 'issued'], true);
    $hasMissingMovement = $materialRequest->items->contains(fn ($it) => ! isset($movementsByItemId[$it->id]));
@endphp

@if($materialRequest->status === 'submitted' || ($isProcessed && $hasMissingMovement))
    <div class="alert alert-warning">Periksa bukti struk / nota (jika terlampir), lalu pilih <strong>Approve Barang Masuk / Restock (+)</strong> untuk menambah stok divisi atau <strong>Issue Stock (-)</strong> untuk mengurangi stok gudang.</div>
@elseif($isProcessed)
    <div class="alert alert-success">Dokumen Stok In sudah disetujui dan saldo <code>inventory_stocks</code> telah diperbarui melalui stock movement.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h5 class="card-title mb-0">{{ $judul }}</h5>
            @if(in_array($materialRequest->status, ['draft', 'cancelled'], true))
                <form method="post" action="{{ route('backend.material-requests.destroy', $materialRequest) }}" onsubmit="return confirm('Hapus dokumen Stok In ini beserta file struknya?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Dokumen</button>
                </form>
            @endif
        </div>
        <hr>
        <div><strong>No Request:</strong> {{ $materialRequest->request_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $materialRequest->request_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $materialRequest->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $materialRequest->division->name ?? '-' }}</div>
        <div><strong>Requester:</strong> {{ $materialRequest->requester->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge {{ $isProcessed ? 'badge-success' : 'badge-info' }} text-uppercase">{{ $materialRequest->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $materialRequest->notes ?? '-' }}</div>

        @if($materialRequest->status === 'submitted' || ($isProcessed && $hasMissingMovement))
            <div class="d-flex flex-wrap gap-2 mt-3">
                <form method="post" action="{{ route('backend.material-requests.issue-stock', $materialRequest) }}">
                    @csrf
                    <input type="hidden" name="mode" value="restock_in">
                    <button class="btn btn-success fw-bold">+ Approve Barang Masuk / Restock Divisi (Tambah Stok)</button>
                </form>
                <form method="post" action="{{ route('backend.material-requests.issue-stock', $materialRequest) }}">
                    @csrf
                    <input type="hidden" name="mode" value="issue_out">
                    <button class="btn btn-primary">&minus; Issue Stock (Kurangi Stok)</button>
                </form>
            </div>
        @endif
    </div>
</div>

@include('partials.material-request-receipt-viewer', [
    'materialRequest' => $materialRequest,
    'downloadRoute' => route('backend.material-requests.receipt.download', $materialRequest),
    'updateRoute' => route('backend.material-requests.receipt.update', $materialRequest),
    'deleteRoute' => route('backend.material-requests.receipt.destroy', $materialRequest),
    'canManageReceipt' => true,
])

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Item</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Request</th>
                        <th>Qty Issue</th>
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
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->requested_qty, 2, ',', '.') }}</td>
                            <td>{{ number_format($issueQty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge badge-success">{{ $movement->movement_type === 'transfer_in' ? 'Restock In (+)' : 'Issued Out (-)' }}</span>
                                @elseif($materialRequest->status === 'issued')
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
        <a href="{{ route('backend.material-requests.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
