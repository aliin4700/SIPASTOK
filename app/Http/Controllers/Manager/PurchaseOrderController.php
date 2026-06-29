<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'status' => ['nullable', Rule::in(PurchaseOrder::STATUSES)],
        ]);

        return view('manager.purchase-orders.index', [
            'purchaseOrders' => PurchaseOrder::with(['supplier', 'outlet', 'items'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
                }))
                ->when($filters['outlet_id'] ?? null, fn ($query, $outletId) => $query->where('outlet_id', $outletId))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'statuses' => PurchaseOrder::STATUSES,
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return view('manager.purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder->load(['supplier', 'outlet', 'orderedBy', 'items.item', 'goodsReceipts']),
        ]);
    }
}
