<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Outlet;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'status' => ['nullable', Rule::in(GoodsReceipt::STATUSES)],
        ]);

        return view('manager.goods-receipts.index', [
            'goodsReceipts' => GoodsReceipt::with(['supplier', 'outlet', 'purchaseOrder', 'items'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', "%{$search}%"));
                }))
                ->when($filters['outlet_id'] ?? null, fn ($query, $outletId) => $query->where('outlet_id', $outletId))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'statuses' => GoodsReceipt::STATUSES,
        ]);
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['supplier', 'outlet', 'purchaseOrder', 'receivedBy', 'appliedBy', 'items.item', 'items.purchaseOrderItem']);
        $movementsByItemId = StockMovement::where('reference_type', GoodsReceiptItem::class)
            ->whereIn('reference_id', $goodsReceipt->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('manager.goods-receipts.show', [
            'goodsReceipt' => $goodsReceipt,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }
}
