@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Waste Monitoring</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No Waste</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Recorder</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Estimasi Nilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->waste_number ?? '-' }}</td>
                            <td>{{ $record->waste_date?->format('d/m/Y') }}</td>
                            <td>{{ $record->outlet->name ?? '-' }}</td>
                            <td>{{ $record->division->name ?? '-' }}</td>
                            <td>{{ $record->recorder->name ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $record->status }}</span></td>
                            <td>{{ $record->items->count() }}</td>
                            <td>Rp {{ number_format((float) $record->items->sum('estimated_value'), 0, ',', '.') }}</td>
                            <td><a href="{{ route('manager.waste-records.show', $record) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada waste record.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</div>
@endsection
