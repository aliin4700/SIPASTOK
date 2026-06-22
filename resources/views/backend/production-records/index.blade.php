@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">{{ $judul }}</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No Produksi</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Producer</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->production_number ?? '-' }}</td>
                            <td>{{ $record->production_date?->format('d/m/Y') }}</td>
                            <td>{{ $record->outlet->name ?? '-' }}</td>
                            <td>{{ $record->division->name ?? '-' }}</td>
                            <td>{{ $record->producer->name ?? '-' }}</td>
                            <td>{{ str_replace('_', ' ', $record->production_type) }}</td>
                            <td><span class="badge badge-info">{{ $record->status }}</span></td>
                            <td>{{ $record->items->count() }}</td>
                            <td><a href="{{ route('backend.production-records.show', $record) }}" class="btn btn-sm btn-cyan">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada production record.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</div>
@endsection
