@extends('staff.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Input Closing Stock Opname</h4>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @elseif(! $operation || $operation->status !== 'open')
            <div class="alert alert-warning mb-3">Buka Operasional Hari Ini terlebih dahulu sebelum Closing Stock Opname.</div>
            <a href="{{ route('staff.daily-operations.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Buka Operasional</a>
        @else
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form method="get" class="row mb-3">
                    <div class="col-md-3">
                        <select name="outlet_id" class="form-control">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="division_id" class="form-control">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
                </form>
            @endif

            <form method="get" class="row mb-3">
                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">
                <div class="col-md-4"><input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari item/kode"></div>
                <div class="col-md-3">
                    <select name="item_type" class="form-control">
                        <option value="">Semua Tipe</option>
                        @foreach($itemTypes as $type)
                            <option value="{{ $type }}" @selected($itemType === $type)>{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary">Filter Item</button></div>
            </form>

            <div class="alert alert-info">
                Outlet: <strong>{{ $operation->outlet->name ?? '-' }}</strong> |
                Divisi: <strong>{{ $operation->division->name ?? '-' }}</strong> |
                Status: <strong>{{ $operation->status }}</strong>
            </div>

            <form method="post" action="{{ route('staff.stock-opnames.store') }}">
                @csrf
                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Tipe</th>
                                <th>System Stock</th>
                                <th>Physical Stock</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $i => $item)
                                @php($stock = $stocks->get($item->id))
                                <tr>
                                    <td>
                                        {{ $item->name }}
                                        <input type="hidden" name="items[{{ $i }}][inventory_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td>{{ str_replace('_', ' ', $item->item_type) }}</td>
                                    <td>{{ number_format((float) ($stock?->current_stock ?? 0), 2, ',', '.') }}</td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][physical_stock]" value="{{ old("items.$i.physical_stock") }}" class="form-control"></td>
                                    <td>{{ $stock?->unit ?? $item->base_unit }}</td>
                                    <td><input type="text" name="items[{{ $i }}][notes]" value="{{ old("items.$i.notes") }}" class="form-control"></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Tidak ada item aktif untuk divisi ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="form-group mb-3">
                    <label>Catatan Opname</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Draft</button>
                <a href="{{ route('staff.stock-opnames.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-secondary">Kembali</a>
            </form>
        @endif
    </div>
</div>
@endsection
