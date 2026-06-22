@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Opening Alert Monitoring</h4>
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <select name="outlet_id" class="form-control">
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="division_id" class="form-control">
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
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
                        <th>Tipe</th>
                        <th>Current</th>
                        <th>Minimum</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $stock)
                        <tr>
                            <td>{{ $stock->outlet->name ?? '-' }}</td>
                            <td>{{ $stock->item->name ?? '-' }}</td>
                            <td>{{ str_replace('_', ' ', $stock->item->item_type ?? '-') }}</td>
                            <td>{{ number_format((float) $stock->current_stock, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) ($stock->item->min_stock ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada opening alert.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
