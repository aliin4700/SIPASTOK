@extends('staff.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h4 class="mb-1">{{ $productionRecord->production_number ?? 'Production Record' }}</h4>
                <div class="text-muted">{{ $productionRecord->production_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge bg-info">{{ $productionRecord->status }}</span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $productionRecord->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $productionRecord->division->name ?? '-' }}</div>
        <div><strong>Tipe:</strong> {{ str_replace('_', ' ', $productionRecord->production_type) }}</div>
        <div><strong>Produced by:</strong> {{ $productionRecord->producer->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $productionRecord->notes ?? '-' }}</div>
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
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionRecord->items as $item)
                        <tr>
                            <td>{{ $item->item_name ?? $item->item?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2">
            @if($productionRecord->status === 'draft')
                <form method="post" action="{{ route('staff.production.submit', $productionRecord) }}">
                    @csrf
                    <button class="btn btn-success">Submit</button>
                </form>
            @endif
            @if(in_array($productionRecord->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('staff.production.cancel', $productionRecord) }}">
                    @csrf
                    <button class="btn btn-outline-danger">Cancel</button>
                </form>
            @endif
            <a href="{{ route('staff.production.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection
