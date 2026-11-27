<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('backend.purchase-orders.index', [
            'judul' => 'Purchase Order',
            'purchaseOrders' => PurchaseOrder::query()
                ->select(['id', 'po_number', 'supplier_id', 'outlet_id', 'ordered_by', 'order_date', 'status', 'created_at'])
                ->with(['supplier:id,name', 'outlet:id,name', 'orderedBy:id,name'])
                ->withCount('items')
                ->withSum('items', 'ordered_qty')
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
                }))
                ->when($filters['outlet_id'] ?? null, fn ($query, $outletId) => $query->where('outlet_id', $outletId))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => PurchaseOrder::STATUSES,
        ]);
    }

    public function create()
    {
        $oldItemIds = collect(old('items', []))
            ->pluck('inventory_item_id')
            ->filter()
            ->unique()
            ->values();

        $prefilledItems = $oldItemIds->isNotEmpty()
            ? InventoryItem::whereIn('id', $oldItemIds)
                ->get(['id', 'code', 'name', 'base_unit', 'unit_cost'])
                ->keyBy('id')
            : collect();

        return view('backend.purchase-orders.create', [
            'judul' => 'Tambah Purchase Order',
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'prefilledItems' => $prefilledItems,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $rows = $this->validItemRows($data['items'] ?? [], 'ordered_qty');

        if ($rows === []) {
            return back()->withInput()->with('error', 'Minimal satu item purchase order harus diisi.');
        }

        $purchaseOrder = DB::transaction(function () use ($request, $data, $rows) {
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'outlet_id' => $data['outlet_id'],
                'ordered_by' => $request->user()->id,
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $purchaseOrder->update(['po_number' => $this->number('PO', $purchaseOrder)]);

            foreach ($rows as $row) {
                $purchaseOrder->items()->create([
                    'inventory_item_id' => $row['inventory_item_id'],
                    'ordered_qty' => $row['ordered_qty'],
                    'unit' => $row['unit'] ?? null,
                    'unit_cost' => $row['unit_cost'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $purchaseOrder;
        });

        return redirect()->route('backend.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return view('backend.purchase-orders.show', [
            'judul' => 'Detail Purchase Order',
            'purchaseOrder' => $purchaseOrder->load(['supplier', 'outlet', 'orderedBy', 'items.item', 'goodsReceipts']),
        ]);
    }

    public function submit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Hanya purchase order draft yang bisa disubmit.');
        }

        $purchaseOrder->update(['status' => 'submitted', 'submitted_at' => now()]);

        return back()->with('success', 'Purchase order berhasil disubmit.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if (! in_array($purchaseOrder->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Purchase order ini tidak bisa dibatalkan.');
        }

        $purchaseOrder->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return back()->with('success', 'Purchase order berhasil dibatalkan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.ordered_qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function validItemRows(array $rows, string $qtyField): array
    {
        return collect($rows)
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && is_numeric($row[$qtyField] ?? null) && (float) $row[$qtyField] > 0)
            ->values()
            ->all();
    }

    private function number(string $prefix, PurchaseOrder $purchaseOrder): string
    {
        $code = $purchaseOrder->outlet?->code ?: 'OUT';

        return "SP-{$code}-{$prefix}-" . now()->format('Ymd') . '-' . str_pad((string) $purchaseOrder->id, 4, '0', STR_PAD_LEFT);
    }
}
