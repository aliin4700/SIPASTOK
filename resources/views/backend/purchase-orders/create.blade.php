@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <form method="post" action="{{ route('backend.purchase-orders.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">Tanpa Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Outlet</label>
                        <select name="outlet_id" class="form-control @error('outlet_id') is-invalid @enderror" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        @error('outlet_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tanggal Order</label>
                        <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Estimasi Datang</label>
                        <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>

            <h6 class="mt-4">Item</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Inventory Item</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 10; $i++)
                            <tr>
                                <td>
                                    <select name="items[{{ $i }}][inventory_item_id]" class="form-control">
                                        <option value="">Pilih Item</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" @selected(old("items.$i.inventory_item_id") == $item->id)>{{ $item->name }} ({{ $item->base_unit }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][ordered_qty]" value="{{ old("items.$i.ordered_qty") }}" class="form-control"></td>
                                <td><input type="text" name="items[{{ $i }}][unit]" value="{{ old("items.$i.unit") }}" class="form-control"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]" value="{{ old("items.$i.unit_cost") }}" class="form-control"></td>
                                <td><input type="text" name="items[{{ $i }}][notes]" value="{{ old("items.$i.notes") }}" class="form-control"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary">Simpan PO</button>
            <a href="{{ route('backend.purchase-orders.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection
