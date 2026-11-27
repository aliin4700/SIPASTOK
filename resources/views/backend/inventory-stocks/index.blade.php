@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        <form method="get" action="{{ route('backend.inventory-stocks.index') }}" class="row mb-3">
            <div class="col-md-4">
                <select name="outlet_id" class="form-control">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit">Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Outlet</th>
                        <th>Item</th>
                        <th>Current</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th class="text-right">Unit Cost (Rp)</th>
                        <th class="text-right">Total Nilai Stok (Rp)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $unitCost = (float) (($stock->item?->unit_cost ?? 0) > 0 ? $stock->item->unit_cost : ($stock->item?->last_purchase_price ?? 0));
                            $stockValue = (float) $stock->current_stock * $unitCost;
                        @endphp
                        <tr>
                            <td>{{ $stock->outlet->name ?? '-' }}</td>
                            <td>
                                <strong>{{ $stock->item->name ?? '-' }}</strong>
                                <div class="text-muted small">{{ $stock->item->division->name ?? 'Umum' }}</div>
                            </td>
                            <td>{{ number_format((float) $stock->current_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td>{{ number_format((float) $stock->reserved_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td>{{ number_format((float) $stock->available_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td class="text-right">Rp {{ number_format($unitCost, 2, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">Rp {{ number_format($stockValue, 2, ',', '.') }}</td>
                            <td><a href="{{ route('backend.inventory-stocks.edit', $stock) }}" class="btn btn-cyan btn-sm">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada data stok. Stok dibuat otomatis saat inventory item dibuat pada tahap berikutnya atau dapat diisi lewat seed/import.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $stocks->links() }}
    </div>
</div>
@endsection
