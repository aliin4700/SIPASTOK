@extends('backend.v_layouts.app')

@section('content')
@if($stockOpname->status === 'submitted')
    <div class="alert alert-warning">Review akan menyesuaikan stok inventory sesuai physical stock dan membuat stock movement.</div>
@elseif($stockOpname->status === 'reviewed')
    <div class="alert alert-success">Stock adjustment sudah diterapkan melalui stock movement.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>No Opname:</strong> {{ $stockOpname->opname_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $stockOpname->opname_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $stockOpname->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $stockOpname->division->name ?? '-' }}</div>
        <div><strong>Counter:</strong> {{ $stockOpname->counter->name ?? '-' }}</div>
        <div><strong>Reviewer:</strong> {{ $stockOpname->reviewer->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $stockOpname->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $stockOpname->notes ?? '-' }}</div>

        @if($stockOpname->status === 'submitted')
            <form method="post" action="{{ route('backend.stock-opnames.review', $stockOpname) }}" class="mt-3">
                @csrf
                <button class="btn btn-success">Review & Apply Stock Adjustment</button>
            </form>
        @endif
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
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->items as $item)
                        @php($movement = $movementsByItemId[$item->id] ?? null)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ str_replace('_', ' ', $item->item->item_type ?? '-') }}</td>
                            <td>{{ number_format((float) $item->system_stock, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->physical_stock, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->difference_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge badge-success">Applied</span>
                                @elseif($stockOpname->status === 'reviewed' && (float) $item->difference_qty == 0.0)
                                    <span class="badge badge-secondary">Zero Difference</span>
                                @elseif($stockOpname->status === 'reviewed')
                                    <span class="badge badge-warning">No Movement</span>
                                @else
                                    <span class="badge badge-secondary">Pending Review</span>
                                @endif
                            </td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('backend.stock-opnames.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
