@extends($layout)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Impor Pemakaian Bahan ESB POS</h4>
        <small class="text-muted">Sinkronisasi laporan <em>Sales Material Usage Report (.xlsx)</em> dari kasir ESB ke saldo stok SIPASTOK.</small>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">1. Unggah Laporan Sales Material Usage ESB</h6>
    </div>
    <div class="card-body">
        <form action="{{ route($routePrefix . '.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="outlet_id" class="form-label fw-semibold">Outlet Tujuan <span class="text-danger">*</span></label>
                    <select name="outlet_id" id="outlet_id" class="form-select" required>
                        <option value="">-- Pilih Outlet --</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>
                                {{ $outlet->name }} ({{ $outlet->code }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Kolom <code>Branch</code> di ESB berisi nama brand umum, sehingga outlet tujuan ditentukan di sini.</small>
                </div>

                <div class="col-md-3">
                    <label for="report_date" class="form-label fw-semibold">Tanggal / Periode Laporan <span class="text-danger">*</span></label>
                    <input type="date" name="report_date" id="report_date" class="form-control" value="{{ old('report_date', now()->toDateString()) }}" required>
                    <small class="text-muted">Digunakan sebagai acuan tanggal batch & pencegahan impor ganda.</small>
                </div>

                <div class="col-md-5">
                    <label for="esb_file" class="form-label fw-semibold">File Excel ESB (.xlsx) <span class="text-danger">*</span></label>
                    <input type="file" name="esb_file" id="esb_file" class="form-control" accept=".xlsx" required>
                    <small class="text-muted">Format ekspor <em>Sales Material Usage Report</em> dari ESB ERP (maks. 10 MB).</small>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    Pratinjau &amp; Cocokkan Item &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Riwayat Batch Impor ESB</h6>
        <span class="badge bg-secondary">{{ $imports->total() }} batch</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Batch</th>
                        <th>Tanggal Laporan</th>
                        <th>Outlet</th>
                        <th>Nama File &amp; Periode</th>
                        <th class="text-end">Baris ESB</th>
                        <th class="text-end">Item Dipotong</th>
                        <th class="text-end">Dilewati</th>
                        <th>Diimpor Oleh</th>
                        <th>Waktu Impor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $batch)
                        <tr>
                            <td class="fw-semibold">#{{ $batch->id }}</td>
                            <td>{{ $batch->report_date?->format('d/m/Y') }}</td>
                            <td>{{ $batch->outlet->name ?? '-' }}</td>
                            <td>
                                <div class="fw-semibold text-break">{{ $batch->file_name }}</div>
                                @if($batch->report_period)
                                    <small class="text-muted">Periode ESB: {{ $batch->report_period }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($batch->total_rows) }}</td>
                            <td class="text-end"><span class="badge bg-success">{{ number_format($batch->processed_items) }} item</span></td>
                            <td class="text-end"><span class="badge bg-light text-dark border">{{ number_format($batch->skipped_rows) }}</span></td>
                            <td>{{ $batch->importer->name ?? '-' }}</td>
                            <td>{{ $batch->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat impor pemakaian bahan ESB.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($imports->hasPages())
        <div class="card-footer bg-white">
            {{ $imports->links() }}
        </div>
    @endif
</div>
@endsection
