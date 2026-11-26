<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class GoodsReceiptController extends Controller
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

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

    public function applyStock(Request $request, GoodsReceipt $goodsReceipt)
    {
        if ($goodsReceipt->status !== 'submitted') {
            return back()->with('error', 'Hanya goods receipt submitted yang bisa apply stock.');
        }

        $goodsReceipt->load(['items.item', 'items.purchaseOrderItem', 'purchaseOrder.items']);

        if ($goodsReceipt->items->isEmpty()) {
            return back()->with('error', 'Goods receipt belum memiliki item.');
        }

        foreach ($goodsReceipt->items as $item) {
            if (! $item->item || (float) $item->received_qty <= 0) {
                return back()->with('error', 'Item goods receipt tidak valid. Pastikan inventory item ada dan qty lebih dari 0.');
            }
        }

        try {
            $summary = DB::transaction(function () use ($request, $goodsReceipt) {
                $summary = ['increased' => 0, 'duplicate' => 0];

                foreach ($goodsReceipt->items as $item) {
                    $result = $this->stockMovementService->increaseStock([
                        'outlet_id' => $goodsReceipt->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity' => $item->received_qty,
                        'movement_type' => 'purchase_in',
                        'reference_type' => GoodsReceiptItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $request->user()->id,
                        'notes' => 'Purchase in from goods receipt ' . ($goodsReceipt->receipt_number ?? $goodsReceipt->id),
                    ]);

                    $result['duplicate'] ? $summary['duplicate']++ : $summary['increased']++;

                    if (! $result['duplicate'] && $item->purchaseOrderItem) {
                        $item->purchaseOrderItem->increment('received_qty', $item->received_qty);
                    }

                    if (! $result['duplicate'] && (float) $item->unit_cost > 0 && $item->item) {
                        $item->item->update([
                            'last_purchase_price' => (float) $item->unit_cost,
                        ]);
                    }
                }

                $goodsReceipt->update([
                    'status' => 'applied',
                    'applied_by' => $request->user()->id,
                    'applied_at' => now(),
                ]);

                $purchaseOrder = $goodsReceipt->purchaseOrder;
                if ($purchaseOrder) {
                    $purchaseOrder->load('items');
                    $allReceived = $purchaseOrder->items->every(fn ($i) => (float) $i->received_qty >= (float) $i->ordered_qty);
                    $anyReceived = $purchaseOrder->items->contains(fn ($i) => (float) $i->received_qty > 0);
                    $purchaseOrder->update([
                        'status' => $allReceived ? 'received' : ($anyReceived ? 'partially_received' : $purchaseOrder->status),
                    ]);
                }

                return $summary;
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Apply stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Stok Goods Receipt berhasil ditambahkan (+{$summary['increased']} item, duplikat diabaikan: {$summary['duplicate']}).");
    }
}
