@extends('backend.v_layouts.app')

@section('content')
@if($wasteRecord->status === 'submitted')
    <div class="alert alert-warning">Apply stock akan mengurangi stok inventory sesuai item waste dan membuat stock movement.</div>
@elseif($wasteRecord->status === 'applied')
    <div class="alert alert-success">Waste stock deduction sudah diterapkan melalui stock movement.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>No Waste:</strong> {{ $wasteRecord->waste_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $wasteRecord->waste_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $wasteRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $wasteRecord->division->name ?? '-' }}</div>
        <div><strong>Recorder:</strong> {{ $wasteRecord->recorder->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $wasteRecord->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $wasteRecord->notes ?? '-' }}</div>

        @if($wasteRecord->status === 'submitted')
            <form method="post" action="{{ route('backend.waste-records.apply-stock', $wasteRecord) }}" class="mt-3">
                @csrf
                <button class="btn btn-success">Apply Stock Deduction</button>
            </form>
        @endif
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
                                    <span class="badge badge-success">Applied</span>
                                @elseif($wasteRecord->status === 'applied')
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
        <a href="{{ route('backend.waste-records.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
