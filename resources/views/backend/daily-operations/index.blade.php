@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">{{ $judul }}</h5>
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
                        <th>Approved Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operations as $operation)
                        <tr>
                            <td>{{ $operation->operation_date?->format('d/m/Y') }}</td>
                            <td>{{ $operation->outlet->name ?? '-' }}</td>
                            <td>{{ $operation->division->name ?? '-' }}</td>
                            <td><span class="badge badge-info">{{ $operation->status }}</span></td>
                            <td>{{ $operation->opener->name ?? '-' }}</td>
                            <td>{{ $operation->submitter->name ?? '-' }}</td>
                            <td>{{ $operation->approver->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('backend.daily-operations.show', $operation) }}" class="btn btn-sm btn-cyan">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada daily operation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $operations->links() }}
    </div>
</div>
@endsection
