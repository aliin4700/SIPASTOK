<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
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

        return view('backend.goods-receipts.index', [
            'judul' => 'Goods Receipt',
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

    public function create(Request $request)
    {
        $selectedPurchaseOrder = PurchaseOrder::with(['supplier', 'outlet', 'items.item'])
            ->find($request->integer('purchase_order_id'));

        return view('backend.goods-receipts.create', [
            'judul' => 'Tambah Goods Receipt',
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'items' => InventoryItem::where('is_active', true)->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['submitted', 'partially_received'])->latest()->get(),
            'selectedPurchaseOrder' => $selectedPurchaseOrder,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $purchaseOrder = empty($data['purchase_order_id'])
            ? null
            : PurchaseOrder::with('items')->findOrFail($data['purchase_order_id']);
        $allowedPoItemIds = $purchaseOrder?->items->pluck('id')->all() ?? [];
        $rows = $this->validItemRows($data['items'] ?? []);

        if ($rows === []) {
            return back()->withInput()->with('error', 'Minimal satu item goods receipt harus diisi.');
        }

        if ($purchaseOrder) {
            foreach ($rows as $row) {
                if (empty($row['purchase_order_item_id']) || ! in_array((int) $row['purchase_order_item_id'], $allowedPoItemIds, true)) {
                    return back()->withInput()->with('error', 'Item goods receipt harus berasal dari purchase order yang dipilih.');
                }
            }
        }

        $goodsReceipt = DB::transaction(function () use ($request, $data, $rows, $purchaseOrder) {
            $goodsReceipt = GoodsReceipt::create([
                'purchase_order_id' => $purchaseOrder?->id,
                'supplier_id' => $purchaseOrder?->supplier_id ?? ($data['supplier_id'] ?? null),
                'outlet_id' => $purchaseOrder?->outlet_id ?? $data['outlet_id'],
                'received_by' => $request->user()->id,
                'receipt_date' => $data['receipt_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $goodsReceipt->update(['receipt_number' => $this->number($goodsReceipt)]);

            foreach ($rows as $row) {
                $goodsReceipt->items()->create([
                    'purchase_order_item_id' => $row['purchase_order_item_id'] ?? null,
                    'inventory_item_id' => $row['inventory_item_id'],
                    'received_qty' => $row['received_qty'],
                    'unit' => $row['unit'] ?? null,
                    'unit_cost' => $row['unit_cost'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $goodsReceipt;
        });

        return redirect()->route('backend.goods-receipts.show', $goodsReceipt)->with('success', 'Goods receipt berhasil dibuat.');
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['supplier', 'outlet', 'purchaseOrder', 'receivedBy', 'appliedBy', 'items.item', 'items.purchaseOrderItem']);
        $movementsByItemId = StockMovement::where('reference_type', GoodsReceiptItem::class)
            ->whereIn('reference_id', $goodsReceipt->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('backend.goods-receipts.show', [
            'judul' => 'Detail Goods Receipt',
            'goodsReceipt' => $goodsReceipt,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function submit(GoodsReceipt $goodsReceipt)
    {
        if ($goodsReceipt->status !== 'draft') {
            return back()->with('error', 'Hanya goods receipt draft yang bisa disubmit.');
        }

        $goodsReceipt->update(['status' => 'submitted']);

        return back()->with('success', 'Goods receipt berhasil disubmit.');
    }

    public function cancel(GoodsReceipt $goodsReceipt)
    {
        if (! in_array($goodsReceipt->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Goods receipt ini tidak bisa dibatalkan.');
        }

        $goodsReceipt->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return back()->with('success', 'Goods receipt berhasil dibatalkan.');
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

                $this->refreshPurchaseOrderStatus($goodsReceipt);

                return $summary;
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Apply stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Goods receipt applied. Increased: {$summary['increased']}, duplicate ignored: {$summary['duplicate']}.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'receipt_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.received_qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function validItemRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && is_numeric($row['received_qty'] ?? null) && (float) $row['received_qty'] > 0)
            ->values()
            ->all();
    }

    private function refreshPurchaseOrderStatus(GoodsReceipt $goodsReceipt): void
    {
        $purchaseOrder = $goodsReceipt->purchaseOrder;

        if (! $purchaseOrder) {
            return;
        }

        $purchaseOrder->load('items');
        $allReceived = $purchaseOrder->items->every(fn ($item) => (float) $item->received_qty >= (float) $item->ordered_qty);
        $anyReceived = $purchaseOrder->items->contains(fn ($item) => (float) $item->received_qty > 0);

        $purchaseOrder->update([
            'status' => $allReceived ? 'received' : ($anyReceived ? 'partially_received' : $purchaseOrder->status),
        ]);
    }

    private function number(GoodsReceipt $goodsReceipt): string
    {
        $code = $goodsReceipt->outlet?->code ?: 'OUT';

        return 'BL-' . $code . '-GR-' . now()->format('Ymd') . '-' . str_pad((string) $goodsReceipt->id, 4, '0', STR_PAD_LEFT);
    }
}
