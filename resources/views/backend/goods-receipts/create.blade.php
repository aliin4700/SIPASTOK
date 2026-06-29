@extends('backend.v_layouts.app')

@section('content')
@php
    $selectedRows = $selectedPurchaseOrder
        ? $selectedPurchaseOrder->items->map(fn ($poItem) => [
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $poItem->inventory_item_id,
            'received_qty' => max(0, (float) $poItem->ordered_qty - (float) $poItem->received_qty),
            'unit' => $poItem->unit ?: ($poItem->item->base_unit ?? null),
            'unit_cost' => $poItem->unit_cost,
            'notes' => $poItem->notes,
        ])->values()->all()
        : [];
    $oldRows = old('items', $selectedRows);
@endphp

<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <form method="post" action="{{ route('backend.goods-receipts.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Purchase Order</label>
                        <select name="purchase_order_id" class="form-control">
                            <option value="">Standalone</option>
                            @foreach($purchaseOrders as $purchaseOrder)
                                <option value="{{ $purchaseOrder->id }}" @selected(old('purchase_order_id', $selectedPurchaseOrder?->id) == $purchaseOrder->id)>{{ $purchaseOrder->po_number }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih dari detail PO untuk prefill item.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">Tanpa Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedPurchaseOrder?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Outlet</label>
                        <select name="outlet_id" class="form-control @error('outlet_id') is-invalid @enderror" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $selectedPurchaseOrder?->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        @error('outlet_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tanggal Terima</label>
                        <input type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>

            <h6 class="mt-4">Item Diterima</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Inventory Item</th>
                            <th>Qty Terima</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 10; $i++)
                            @php($row = $oldRows[$i] ?? [])
                            <tr>
                                <td>
                                    <input type="hidden" name="items[{{ $i }}][purchase_order_item_id]" value="{{ $row['purchase_order_item_id'] ?? '' }}">
                                    <select name="items[{{ $i }}][inventory_item_id]" class="form-control">
                                        <option value="">Pilih Item</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" @selected(($row['inventory_item_id'] ?? null) == $item->id)>{{ $item->name }} ({{ $item->base_unit }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][received_qty]" value="{{ $row['received_qty'] ?? '' }}" class="form-control"></td>
                                <td><input type="text" name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}" class="form-control"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]" value="{{ $row['unit_cost'] ?? '' }}" class="form-control"></td>
                                <td><input type="text" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="form-control"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary">Simpan Goods Receipt</button>
            <a href="{{ route('backend.goods-receipts.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection
