@extends('staff.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h4 class="mb-1">{{ $wasteRecord->waste_number ?? 'Waste Record' }}</h4>
                <div class="text-muted">{{ $wasteRecord->waste_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge bg-info">{{ $wasteRecord->status }}</span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $wasteRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $wasteRecord->division->name ?? '-' }}</div>
        <div><strong>Recorded by:</strong> {{ $wasteRecord->recorder->name ?? '-' }}</div>
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
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wasteRecord->items as $item)
                        <tr>
                            <td>{{ $item->item_name ?? $item->item?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td>{{ str_replace('_', ' ', $item->reason) }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2">
            @if($wasteRecord->status === 'draft')
                <form method="post" action="{{ route('staff.waste.submit', $wasteRecord) }}">
                    @csrf
                    <button class="btn btn-success">Submit</button>
                </form>
            @endif
            @if(in_array($wasteRecord->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('staff.waste.cancel', $wasteRecord) }}">
                    @csrf
                    <button class="btn btn-outline-danger">Cancel</button>
                </form>
            @endif
            <a href="{{ route('staff.waste.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection
