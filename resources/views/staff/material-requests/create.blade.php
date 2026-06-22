@extends('staff.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Buat Take Bahan</h4>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @elseif(! $operation || $operation->status !== 'open')
            <div class="alert alert-warning mb-3">Buka Operasional Hari Ini terlebih dahulu sebelum membuat Take Bahan.</div>
            <a href="{{ route('staff.daily-operations.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Buka Operasional</a>
        @else
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form method="get" class="row mb-3">
                    <div class="col-md-4">
                        <select name="outlet_id" class="form-control">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="division_id" class="form-control">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
                </form>
            @endif

            <div class="alert alert-info">
                Outlet: <strong>{{ $operation->outlet->name ?? '-' }}</strong> |
                Divisi: <strong>{{ $operation->division->name ?? '-' }}</strong> |
                Status: <strong>{{ $operation->status }}</strong>
            </div>

            <form method="post" action="{{ route('staff.material-requests.store') }}">
                @csrf
                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 40%">Item</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 10; $i++)
                                <tr>
                                    <td>
                                        <select name="items[{{ $i }}][inventory_item_id]" class="form-control">
                                            <option value="">Pilih item</option>
                                            @foreach($items as $item)
                                                @php($stock = $stocks->get($item->id))
                                                <option value="{{ $item->id }}" @selected(old("items.$i.inventory_item_id") == $item->id)>
                                                    {{ $item->name }} ({{ $item->item_type }}) - Stok {{ number_format((float) ($stock?->current_stock ?? 0), 2, ',', '.') }} {{ $stock?->unit ?? $item->base_unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" min="0.01" name="items[{{ $i }}][requested_qty]" value="{{ old("items.$i.requested_qty") }}" class="form-control"></td>
                                    <td><input type="text" name="items[{{ $i }}][unit]" value="{{ old("items.$i.unit") }}" class="form-control" placeholder="auto"></td>
                                    <td><input type="text" name="items[{{ $i }}][notes]" value="{{ old("items.$i.notes") }}" class="form-control"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="form-group mb-3">
                    <label>Catatan Request</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Draft</button>
                <a href="{{ route('staff.material-requests.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-secondary">Kembali</a>
            </form>
        @endif
    </div>
</div>
@endsection
