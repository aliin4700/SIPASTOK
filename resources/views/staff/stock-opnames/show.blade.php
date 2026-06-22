@extends('staff.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h4 class="mb-1">{{ $stockOpname->opname_number ?? 'Stock Opname' }}</h4>
                <div class="text-muted">{{ $stockOpname->opname_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge bg-info">{{ $stockOpname->status }}</span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $stockOpname->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $stockOpname->division->name ?? '-' }}</div>
        <div><strong>Counted by:</strong> {{ $stockOpname->counter->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $stockOpname->notes ?? '-' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Item Opname</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tipe</th>
                        <th>System</th>
                        <th>Physical</th>
                        <th>Selisih</th>
                        <th>Unit</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->items as $item)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ str_replace('_', ' ', $item->item->item_type ?? '-') }}</td>
                            <td>{{ number_format((float) $item->system_stock, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->physical_stock, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->difference_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2">
            @if($stockOpname->status === 'draft')
                <form method="post" action="{{ route('staff.stock-opnames.submit', $stockOpname) }}">
                    @csrf
                    <button class="btn btn-success">Submit</button>
                </form>
            @endif
            @if(in_array($stockOpname->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('staff.stock-opnames.cancel', $stockOpname) }}">
                    @csrf
                    <button class="btn btn-outline-danger">Cancel</button>
                </form>
            @endif
            <a href="{{ route('staff.stock-opnames.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection
