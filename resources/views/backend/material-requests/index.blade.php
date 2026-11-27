@extends('backend.v_layouts.app')

@section('content')
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="card-title mb-0">{{ $judul }}</h5>
            <form id="bulkApproveForm" method="post" action="{{ route('backend.material-requests.bulk-approve') }}" onsubmit="return confirm('Setujui semua dokumen Stok In yang terpilih sekaligus (+ Restock ke stok)?');">
                @csrf
                <button type="submit" id="bulkApproveBtn" class="btn btn-success btn-sm fw-semibold" disabled>
                    &#10003; Approve Terpilih (<span id="bulkSelectedCount">0</span>)
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="bulkSelectAll" title="Pilih semua yang menunggu persetujuan">
                        </th>
                        <th>No Request</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Requester</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Bukti Nota</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td class="text-center">
                                @if($requestItem->status === 'submitted')
                                    <input type="checkbox" name="ids[]" value="{{ $requestItem->id }}" form="bulkApproveForm" class="bulk-item-check">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $requestItem->request_number ?? '-' }}</td>
                            <td>{{ $requestItem->request_date?->format('d/m/Y') }}</td>
                            <td>{{ $requestItem->outlet->name ?? '-' }}</td>
                            <td>{{ $requestItem->division->name ?? '-' }}</td>
                            <td>{{ $requestItem->requester->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ in_array($requestItem->status, ['approved', 'issued'], true) ? 'badge-success' : ($requestItem->status === 'submitted' ? 'badge-warning' : 'badge-info') }} text-uppercase">
                                    {{ $requestItem->status }}
                                </span>
                            </td>
                            <td>{{ $requestItem->items->count() }}</td>
                            <td class="text-center">
                                @include('partials.material-request-receipt-badge', ['materialRequest' => $requestItem])
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('backend.material-requests.show', $requestItem) }}" class="btn btn-sm btn-cyan">Detail</a>
                                    @if($requestItem->status === 'submitted')
                                        <form method="post" action="{{ route('backend.material-requests.issue-stock', $requestItem) }}" class="d-inline" onsubmit="return confirm('Setujui Stok In ini dan tambahkan ke saldo stok (+ Restock)?');">
                                            @csrf
                                            <input type="hidden" name="mode" value="restock_in">
                                            <button type="submit" class="btn btn-sm btn-success">&#10003; Approve</button>
                                        </form>
                                    @endif
                                    @if(in_array($requestItem->status, ['draft', 'cancelled'], true))
                                        <form method="post" action="{{ route('backend.material-requests.destroy', $requestItem) }}" class="d-inline" onsubmit="return confirm('Hapus dokumen Stok In ini beserta lampiran struknya?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">Belum ada data Stok In.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('bulkSelectAll');
    const checkboxes = Array.from(document.querySelectorAll('.bulk-item-check'));
    const btn = document.getElementById('bulkApproveBtn');
    const countEl = document.getElementById('bulkSelectedCount');

    function syncBulkState() {
        const checked = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (countEl) countEl.textContent = String(checked);
        if (btn) btn.disabled = checked === 0;
        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = checked === checkboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            syncBulkState();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', syncBulkState);
    });
    syncBulkState();
});
</script>
@endsection
