@extends('backend.v_layouts.app')

@section('content')
@php
    $isProcessed = in_array($productionRecord->status, ['approved', 'applied'], true);
    $hasMissingMovement = $productionRecord->items->contains(fn ($it) => ! isset($movementsByItemId[$it->id]));
@endphp

@if($productionRecord->status === 'submitted' || ($isProcessed && $hasMissingMovement))
    <div class="alert alert-warning">Apply stock akan menambah stok inventory sesuai item hasil produksi dan otomatis memotong bahan baku jika item memiliki Resep / BOM aktif (<code>output_inventory_item_id</code>).</div>
@elseif($isProcessed)
    <div class="alert alert-success">Production stock increase sudah disetujui dan saldo <code>inventory_stocks</code> telah diperbarui.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>No Produksi:</strong> {{ $productionRecord->production_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $productionRecord->production_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $productionRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $productionRecord->division->name ?? '-' }}</div>
        <div><strong>Producer:</strong> {{ $productionRecord->producer->name ?? '-' }}</div>
        <div><strong>Tipe:</strong> {{ str_replace('_', ' ', $productionRecord->production_type) }}</div>
        <div><strong>Status:</strong> <span class="badge {{ $isProcessed ? 'badge-success' : 'badge-info' }} text-uppercase">{{ $productionRecord->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $productionRecord->notes ?? '-' }}</div>

        @if($productionRecord->status === 'submitted' || ($isProcessed && $hasMissingMovement))
            <form method="post" action="{{ route('backend.production-records.apply-stock', $productionRecord) }}" class="mt-3 p-3 bg-light border rounded">
                @csrf
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="deduct_bom" value="1" id="backendDeductBomCheck" checked>
                    <label class="form-check-label small font-weight-bold" for="backendDeductBomCheck">
                        Otomatis potong bahan baku mentah sesuai Resep / BOM (jika item produksi memiliki resep aktif)
                    </label>
                </div>
                <button class="btn btn-success">Apply Stock Increase (+ Produksi)</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Item Produksi</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Estimasi Nilai</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionRecord->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td>{{ $item->item_name ?? $item->item?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge badge-success">Applied</span>
                                @elseif($productionRecord->status === 'applied')
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
        <a href="{{ route('backend.production-records.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
