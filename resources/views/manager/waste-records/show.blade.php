@extends('manager.layouts.app')

@section('content')
@if($wasteRecord->status === 'applied')
    <div class="alert alert-success">Monitoring ini read-only. Waste stock deduction sudah diterapkan melalui stock movement.</div>
@else
    <div class="alert alert-warning">Monitoring ini read-only. Stock deduction diterapkan oleh admin/super admin dari backend.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3">Detail Waste Record</h4>
        <div><strong>No Waste:</strong> {{ $wasteRecord->waste_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $wasteRecord->waste_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $wasteRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $wasteRecord->division->name ?? '-' }}</div>
        <div><strong>Recorder:</strong> {{ $wasteRecord->recorder->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge bg-info">{{ $wasteRecord->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $wasteRecord->notes ?? '-' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Item Waste</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Estimasi Nilai</th>
                        <th>Reason</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wasteRecord->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td>{{ $item->item_name ?? $item->item?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td>{{ str_replace('_', ' ', $item->reason) }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge bg-success">Applied</span>
                                @elseif($wasteRecord->status === 'applied')
                                    <span class="badge bg-warning">No Movement</span>
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
