@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Daily Operation Monitoring</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Status</th>
                        <th>Dibuka Oleh</th>
                        <th>Submitted Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operations as $operation)
                        <tr>
                            <td>{{ $operation->operation_date->format('d/m/Y') }}</td>
                            <td>{{ $operation->outlet->name ?? '-' }}</td>
                            <td>{{ $operation->division->name ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $operation->status }}</span></td>
                            <td>{{ $operation->opener->name ?? '-' }}</td>
                            <td>{{ $operation->submitter->name ?? '-' }}</td>
                            <td><a href="{{ route('manager.daily-operations.show', $operation) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada operasional harian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $operations->links() }}
    </div>
</div>
@endsection
