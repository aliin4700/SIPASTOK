<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryLookupApiController extends Controller
{
    /**
     * Endpoint ringan pencarian item untuk form PO & Goods Receipt (maks. 25 item).
     */
    public function searchItems(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $items = InventoryItem::query()
            ->select([
                'id',
                'code',
                'name',
                'esb_item_name',
                'base_unit',
                'purchase_unit',
                'unit_conversion_factor',
                'unit_cost',
                'last_purchase_price',
            ])
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q, $likeOperator) {
                $pattern = '%' . $q . '%';
                $query->where(function ($inner) use ($pattern, $likeOperator) {
                    $inner->where('name', $likeOperator, $pattern)
                        ->orWhere('code', $likeOperator, $pattern)
                        ->orWhere('esb_item_name', $likeOperator, $pattern);
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(function (InventoryItem $item) {
                $cost = (float) ($item->unit_cost > 0 ? $item->unit_cost : ($item->last_purchase_price ?? 0));

                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'esb_item_name' => $item->esb_item_name,
                    'unit' => $item->base_unit,
                    'purchase_unit' => $item->purchase_unit ?: $item->base_unit,
                    'unit_conversion_factor' => (float) ($item->unit_conversion_factor ?: 1),
                    'unit_cost' => round($cost, 4),
                ];
            })
            ->values();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Endpoint on-demand untuk memuat item dari nomor PO terpilih di form Goods Receipt.
     */
    public function purchaseOrderItems(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load([
            'items' => fn ($q) => $q->select([
                'id',
                'purchase_order_id',
                'inventory_item_id',
                'ordered_qty',
                'received_qty',
                'unit',
                'unit_cost',
                'notes',
            ])->with('item:id,code,name,base_unit,unit_cost,last_purchase_price'),
        ]);

        $items = $purchaseOrder->items->map(function ($poItem) {
            $remaining = max(0.0, (float) $poItem->ordered_qty - (float) $poItem->received_qty);
            $fallbackCost = $poItem->item
                ? (float) ($poItem->item->unit_cost > 0 ? $poItem->item->unit_cost : ($poItem->item->last_purchase_price ?? 0))
                : 0.0;

            return [
                'purchase_order_item_id' => $poItem->id,
                'inventory_item_id' => $poItem->inventory_item_id,
                'code' => $poItem->item?->code ?? '',
                'name' => $poItem->item?->name ?? '-',
                'ordered_qty' => (float) $poItem->ordered_qty,
                'received_qty' => (float) $poItem->received_qty,
                'remaining_qty' => round($remaining, 2),
                'unit' => $poItem->unit ?: ($poItem->item?->base_unit ?? ''),
                'unit_cost' => (float) ($poItem->unit_cost !== null ? $poItem->unit_cost : $fallbackCost),
                'notes' => $poItem->notes ?? '',
            ];
        })->values();

        return response()->json([
            'purchase_order' => [
                'id' => $purchaseOrder->id,
                'po_number' => $purchaseOrder->po_number,
                'outlet_id' => $purchaseOrder->outlet_id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'status' => $purchaseOrder->status,
            ],
            'items' => $items,
        ]);
    }
}
