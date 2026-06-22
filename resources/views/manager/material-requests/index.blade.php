@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Material Request Monitoring</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No Request</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Requester</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td>{{ $requestItem->request_number ?? '-' }}</td>
                            <td>{{ $requestItem->request_date?->format('d/m/Y') }}</td>
                            <td>{{ $requestItem->outlet->name ?? '-' }}</td>
                            <td>{{ $requestItem->division->name ?? '-' }}</td>
                            <td>{{ $requestItem->requester->name ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $requestItem->status }}</span></td>
                            <td>{{ $requestItem->items->count() }}</td>
                            <td><a href="{{ route('manager.material-requests.show', $requestItem) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada material request.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</div>
@endsection
