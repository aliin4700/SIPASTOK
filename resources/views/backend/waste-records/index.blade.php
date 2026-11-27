@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">{{ $judul }}</h5>
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
                            <td>
                                <span class="badge {{ in_array($record->status, ['approved', 'applied'], true) ? 'badge-success' : ($record->status === 'submitted' ? 'badge-warning' : 'badge-info') }} text-uppercase">
                                    {{ $record->status }}
                                </span>
                            </td>
                            <td>{{ $record->items->count() }}</td>
                            <td>Rp {{ number_format((float) $record->items->sum('estimated_value'), 0, ',', '.') }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('backend.waste-records.show', $record) }}" class="btn btn-sm btn-cyan">Detail</a>
                                    @if($record->status === 'submitted')
                                        <form method="post" action="{{ route('backend.waste-records.apply-stock', $record) }}" class="d-inline" onsubmit="return confirm('Setujui Waste ini dan potong saldo stok (- Waste)?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">&#10003; Approve</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
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
