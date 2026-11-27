@extends('manager.layouts.app')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 fw-bold">Production Monitoring</h4>
            <form id="bulkApproveForm" method="post" action="{{ route('manager.production-records.bulk-approve') }}" onsubmit="return confirm('Setujui semua dokumen Input Produksi yang terpilih sekaligus (+ Stok WIP & potong BOM)?');">
                @csrf
                <button type="submit" id="bulkApproveBtn" class="btn btn-success btn-sm fw-semibold" disabled>
                    &#10003; Approve Terpilih (<span id="bulkSelectedCount">0</span>)
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="bulkSelectAll" class="form-check-input" title="Pilih semua yang menunggu persetujuan">
                        </th>
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
                            <td class="text-center">
                                @if($record->status === 'submitted')
                                    <input type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulkApproveForm" class="form-check-input bulk-item-check">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $record->production_number ?? '-' }}</td>
                            <td>{{ $record->production_date?->format('d/m/Y') }}</td>
                            <td>{{ $record->outlet->name ?? '-' }}</td>
                            <td>{{ $record->division->name ?? '-' }}</td>
                            <td>{{ $record->producer->name ?? '-' }}</td>
                            <td>{{ str_replace('_', ' ', $record->production_type) }}</td>
                            <td>
                                <span class="badge {{ in_array($record->status, ['approved', 'applied'], true) ? 'bg-success' : ($record->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info text-dark') }} text-uppercase">
                                    {{ $record->status }}
                                </span>
                            </td>
                            <td>{{ $record->items->count() }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('manager.production-records.show', $record) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                    @if($record->status === 'submitted')
                                        <form method="post" action="{{ route('manager.production-records.apply-stock', $record) }}" class="d-inline" onsubmit="return confirm('Setujui hasil produksi ini dan tambahkan ke saldo stok (+ WIP)?');">
                                            @csrf
                                            <input type="hidden" name="deduct_bom" value="1">
                                            <button type="submit" class="btn btn-sm btn-success fw-semibold">&#10003; Approve</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">Belum ada production record.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
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
