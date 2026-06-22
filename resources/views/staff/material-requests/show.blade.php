@extends('staff.layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h4 class="mb-1">{{ $materialRequest->request_number ?? 'Take Bahan' }}</h4>
                <div class="text-muted">{{ $materialRequest->request_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge bg-info">{{ $materialRequest->status }}</span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $materialRequest->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $materialRequest->division->name ?? '-' }}</div>
        <div><strong>Requester:</strong> {{ $materialRequest->requester->name ?? '-' }}</div>
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

        <div class="d-flex gap-2">
            @if($materialRequest->status === 'draft')
                <form method="post" action="{{ route('staff.material-requests.submit', $materialRequest) }}">
                    @csrf
                    <button class="btn btn-success">Submit</button>
                </form>
            @endif
            @if(in_array($materialRequest->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('staff.material-requests.cancel', $materialRequest) }}">
                    @csrf
                    <button class="btn btn-outline-danger">Cancel</button>
                </form>
            @endif
            <a href="{{ route('staff.material-requests.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection
