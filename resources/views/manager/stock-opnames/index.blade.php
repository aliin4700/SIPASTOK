@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Stock Opname Monitoring</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No Opname</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Counter</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Total Selisih</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $opname)
                        <tr>
                            <td>{{ $opname->opname_number ?? '-' }}</td>
                            <td>{{ $opname->opname_date?->format('d/m/Y') }}</td>
                            <td>{{ $opname->outlet->name ?? '-' }}</td>
                            <td>{{ $opname->division->name ?? '-' }}</td>
                            <td>{{ $opname->counter->name ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $opname->status }}</span></td>
                            <td>{{ $opname->items->count() }}</td>
                            <td>{{ number_format((float) $opname->items->sum('difference_qty'), 2, ',', '.') }}</td>
                            <td><a href="{{ route('manager.stock-opnames.show', $opname) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada stock opname.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $opnames->links() }}
    </div>
</div>
@endsection
