@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Inventory Monitoring</h4>
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <select name="outlet_id" class="form-control">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary">Filter</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Outlet</th>
                        <th>Item</th>
                        <th>Divisi</th>
                        <th>Current</th>
                        <th>Reserved</th>
                        <th>Available</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td>{{ $stock->outlet->name ?? '-' }}</td>
                            <td>{{ $stock->item->name ?? '-' }}</td>
                            <td>{{ $stock->item->division->name ?? 'Umum' }}</td>
                            <td>{{ number_format((float) $stock->current_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td>{{ number_format((float) $stock->reserved_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td>{{ number_format((float) $stock->available_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Belum ada stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $stocks->links() }}
    </div>
</div>
@endsection
