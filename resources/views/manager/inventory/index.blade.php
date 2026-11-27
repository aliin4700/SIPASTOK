@extends('manager.layouts.app')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-0 fw-bold">Stok Outlet (Inventory Monitoring)</h4>
                <div class="text-muted small">Pantau saldo stok aktual per outlet, divisi, atau cari berdasarkan nama/kode item.</div>
            </div>
            <span class="badge bg-light text-dark border">Total: {{ $stocks->total() }} data stok</span>
        </div>

        <form method="get" action="{{ route('manager.inventory.index') }}" class="row g-2 align-items-end mb-3 p-3 bg-light rounded border">
            <div class="col-12 col-md-3">
                <label for="filterOutlet" class="form-label small fw-semibold mb-1">Outlet</label>
                <select id="filterOutlet" name="outlet_id" class="form-select">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((string) ($selectedOutletId ?? request('outlet_id')) === (string) $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label for="filterDivision" class="form-label small fw-semibold mb-1">Divisi</label>
                <select id="filterDivision" name="division_id" class="form-select">
                    <option value="">Semua Divisi</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" @selected((string) ($selectedDivisionId ?? request('division_id')) === (string) $division->id)>
                            {{ $division->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-4">
                <label for="filterSearch" class="form-label small fw-semibold mb-1">Cari Item (Nama / Kode SKU)</label>
                <input
                    id="filterSearch"
                    type="search"
                    name="search"
                    value="{{ $search ?? request('search') }}"
                    class="form-control"
                    placeholder="Ketik nama bahan atau kode item..."
                >
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold">Cari / Filter</button>
                @if(request()->filled('outlet_id') || request()->filled('division_id') || request()->filled('search') || request()->filled('q'))
                    <a href="{{ route('manager.inventory.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Outlet</th>
                        <th>Item</th>
                        <th>Divisi</th>
                        <th class="text-end">Current</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Unit Cost (Rp)</th>
                        <th class="text-end">Total Nilai Stok (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $unitCost = (float) (($stock->item?->unit_cost ?? 0) > 0 ? $stock->item->unit_cost : ($stock->item?->last_purchase_price ?? 0));
                            $stockValue = (float) $stock->current_stock * $unitCost;
                        @endphp
                        <tr>
                            <td>{{ $stock->outlet->name ?? '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $stock->item->name ?? '-' }}</div>
                                @if($stock->item?->code)
                                    <div class="text-muted small">{{ $stock->item->code }}</div>
                                @endif
                            </td>
                            <td>{{ $stock->item->division->name ?? 'Umum' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $stock->current_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td class="text-end">{{ number_format((float) $stock->reserved_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td class="text-end">{{ number_format((float) $stock->available_stock, 2, ',', '.') }} {{ $stock->unit }}</td>
                            <td class="text-end">Rp {{ number_format($unitCost, 2, ',', '.') }}</td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($stockValue, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Tidak ada data stok yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $stocks->links() }}
    </div>
</div>
@endsection
