@extends('manager.layouts.app')

@section('content')
@if($wasteRecord->status === 'applied')
    <div class="alert alert-success">Potongan stok Waste sudah diterapkan ke inventori outlet melalui stock movement.</div>
@elseif($wasteRecord->status === 'submitted')
    <div class="alert alert-warning">Dokumen Waste ini berstatus <strong>SUBMITTED</strong>. Klik tombol <strong>Approve &amp; Potong Stok Waste</strong> di bawah untuk mengurangi stok sistem sebelum Stock Opname direview.</div>
@endif

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">Detail Waste Record: {{ $wasteRecord->waste_number ?? '-' }}</h4>
                <div class="text-muted small">Tanggal: {{ $wasteRecord->waste_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge {{ $wasteRecord->status === 'applied' ? 'bg-success' : ($wasteRecord->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info') }} text-uppercase fs-6">
                {{ $wasteRecord->status }}
            </span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $wasteRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $wasteRecord->division->name ?? '-' }}</div>
        <div><strong>Recorder:</strong> {{ $wasteRecord->recorder->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $wasteRecord->notes ?? '-' }}</div>

        @if($wasteRecord->status === 'submitted')
            <form method="post" action="{{ route('manager.waste-records.apply-stock', $wasteRecord) }}" class="mt-3" onsubmit="return confirm('Terapkan pemotongan stok Waste (waste_out) sekarang?');">
                @csrf
                <button type="submit" class="btn btn-danger fw-bold">
                    &#10003; Approve &amp; Potong Stok Waste (Apply Stock)
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-3 fw-bold">Item Waste</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th>Unit</th>
                        <th class="text-end">Estimasi Nilai</th>
                        <th>Reason</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wasteRecord->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td class="fw-semibold">{{ $item->item_name ?? $item->item?->name ?? '-' }}</td>
                            <td class="text-end fw-bold text-danger">-{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ strtoupper($item->unit) }}</td>
                            <td class="text-end">Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $item->reason) }}</span></td>
                            <td>
                                @if($movement)
                                    <span class="badge bg-success">Applied</span>
                                @elseif($wasteRecord->status === 'applied')
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
        <a href="{{ route('manager.waste-records.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
