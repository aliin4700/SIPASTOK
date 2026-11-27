@extends('manager.layouts.app')

@section('content')
@php
    $isProcessed = in_array($productionRecord->status, ['approved', 'applied'], true);
    $hasMissingMovement = $productionRecord->items->contains(fn ($it) => ! isset($movementsByItemId[$it->id]));
@endphp

@if($isProcessed && ! $hasMissingMovement)
    <div class="alert alert-success">Penambahan stok hasil Produksi / WIP sudah disetujui dan diterapkan ke saldo <code>inventory_stocks</code>.</div>
@elseif($productionRecord->status === 'submitted' || ($isProcessed && $hasMissingMovement))
    <div class="alert alert-warning">Dokumen Produksi / WIP ini siap diproses ke stok. Klik tombol <strong>Approve &amp; Tambah Stok Produksi</strong> di bawah untuk memperbarui saldo WIP outlet.</div>
@endif

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">Detail Production Record: {{ $productionRecord->production_number ?? '-' }}</h4>
                <div class="text-muted small">Tanggal: {{ $productionRecord->production_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge {{ $isProcessed ? 'bg-success' : ($productionRecord->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info') }} text-uppercase fs-6">
                {{ $productionRecord->status }}
            </span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $productionRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $productionRecord->division->name ?? '-' }}</div>
        <div><strong>Producer:</strong> {{ $productionRecord->producer->name ?? '-' }}</div>
        <div><strong>Tipe:</strong> {{ str_replace('_', ' ', $productionRecord->production_type) }}</div>
        <div><strong>Catatan:</strong> {{ $productionRecord->notes ?? '-' }}</div>

        @if($productionRecord->status === 'submitted' || ($isProcessed && $hasMissingMovement))
            <form method="post" action="{{ route('manager.production-records.apply-stock', $productionRecord) }}" class="mt-3 p-3 bg-light border rounded" onsubmit="return confirm('Setujui hasil produksi ini dan tambahkan ke saldo stok WIP?');">
                @csrf
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="deduct_bom" value="1" id="deductBomCheck" checked>
                    <label class="form-check-label small fw-semibold" for="deductBomCheck">
                        Otomatis potong bahan baku mentah sesuai Resep / BOM (jika item WIP memiliki resep aktif)
                    </label>
                </div>
                <button type="submit" class="btn btn-success fw-bold">
                    &#10003; Approve &amp; Tambah Stok Produksi (+ WIP)
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-3 fw-bold">Item Produksi</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty Hasil Produksi</th>
                        <th>Unit</th>
                        <th class="text-end">Estimasi Nilai</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionRecord->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td class="fw-semibold">{{ $item->item_name ?? $item->item?->name ?? '-' }}</td>
                            <td class="text-end fw-bold text-success">+{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ strtoupper($item->unit) }}</td>
                            <td class="text-end">Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge bg-success">Applied</span>
                                @elseif($productionRecord->status === 'applied')
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
        <a href="{{ route('manager.production-records.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
