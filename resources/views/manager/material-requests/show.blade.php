@extends('manager.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3">Detail Material Request</h4>
        <div><strong>No Request:</strong> {{ $materialRequest->request_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $materialRequest->request_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $materialRequest->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $materialRequest->division->name ?? '-' }}</div>
        <div><strong>Requester:</strong> {{ $materialRequest->requester->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge bg-info">{{ $materialRequest->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $materialRequest->notes ?? '-' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Item</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Request</th>
                        <th>Unit</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialRequest->items as $item)
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->requested_qty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('manager.material-requests.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
