@extends('backend.v_layouts.app')

@section('content')
@if($productionRecord->status === 'submitted')
    <div class="alert alert-warning">Apply stock akan menambah stok inventory sesuai item hasil produksi. Raw material belum dikurangi pada tahap ini.</div>
@elseif($productionRecord->status === 'applied')
    <div class="alert alert-success">Production stock increase sudah diterapkan melalui stock movement.</div>
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
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $productionRecord->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $productionRecord->notes ?? '-' }}</div>

        @if($productionRecord->status === 'submitted')
            <form method="post" action="{{ route('backend.production-records.apply-stock', $productionRecord) }}" class="mt-3">
                @csrf
                <button class="btn btn-success">Apply Stock Increase</button>
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
