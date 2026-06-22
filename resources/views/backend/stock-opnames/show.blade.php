@extends('backend.v_layouts.app')

@section('content')
<div class="alert alert-warning">Review belum mengubah stok. Adjustment stok akan dibuat pada tahap berikutnya.</div>

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
                <button class="btn btn-success">Review Status</button>
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
        <a href="{{ route('backend.stock-opnames.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
