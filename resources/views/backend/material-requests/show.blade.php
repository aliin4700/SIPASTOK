@extends('backend.v_layouts.app')

@section('content')
@if($materialRequest->status === 'submitted')
    <div class="alert alert-warning">Issue stock akan mengurangi stok inventory sesuai item Take Bahan dan membuat stock movement.</div>
@elseif($materialRequest->status === 'issued')
    <div class="alert alert-success">Material issue sudah diterapkan melalui stock movement.</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>No Request:</strong> {{ $materialRequest->request_number ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $materialRequest->request_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $materialRequest->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $materialRequest->division->name ?? '-' }}</div>
        <div><strong>Requester:</strong> {{ $materialRequest->requester->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $materialRequest->status }}</span></div>
        <div><strong>Catatan:</strong> {{ $materialRequest->notes ?? '-' }}</div>

        @if($materialRequest->status === 'submitted')
            <form method="post" action="{{ route('backend.material-requests.issue-stock', $materialRequest) }}" class="mt-3">
                @csrf
                <button class="btn btn-success">Issue Stock</button>
            </form>
        @endif
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
                        <th>Qty Issue</th>
                        <th>Unit</th>
                        <th>Movement</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialRequest->items as $item)
                        @php
                            $movement = $movementsByItemId[$item->id] ?? null;
                            $issueQty = (float) ($item->issued_qty ?: ($item->approved_qty ?: $item->requested_qty));
                        @endphp
                        <tr>
                            <td>{{ $item->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $item->requested_qty, 2, ',', '.') }}</td>
                            <td>{{ number_format($issueQty, 2, ',', '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>
                                @if($movement)
                                    <span class="badge badge-success">Issued</span>
                                @elseif($materialRequest->status === 'issued')
                                    <span class="badge badge-warning">No Movement</span>
                                @else
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('backend.material-requests.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection
