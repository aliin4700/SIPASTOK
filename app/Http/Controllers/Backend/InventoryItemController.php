<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryItemController extends Controller
{
    private const UNITS = ['pcs', 'butir', 'gr', 'kg', 'ml', 'liter', 'cm', 'pack', 'box', 'tray', 'botol', 'cup', 'slice', 'loaf', 'porsi', 'ekor', 'ikat', 'slop', 'unit'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'item_type' => ['nullable', Rule::in(InventoryItem::TYPES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        return view('backend.inventory-items.index', [
            'judul' => 'Inventory Item',
            'items' => InventoryItem::with('division')
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }))
                ->when($filters['division_id'] ?? null, fn ($query, $divisionId) => $query->where('division_id', $divisionId))
                ->when($filters['item_type'] ?? null, fn ($query, $type) => $query->where('item_type', $type))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'itemTypes' => InventoryItem::TYPES,
        ]);
    }

    public function create()
    {
        return view('backend.inventory-items.create', [
            'judul' => 'Tambah Inventory Item',
            'item' => new InventoryItem(['is_active' => true, 'min_stock' => 0]),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'units' => self::UNITS,
        ]);
    }

    public function store(Request $request)
    {
        $item = InventoryItem::create($this->validated($request));

        Outlet::where('is_active', true)->get()->each(fn ($outlet) => $item->stocks()->create([
            'outlet_id' => $outlet->id,
            'unit' => $item->base_unit,
        ]));

        return redirect()->route('backend.inventory-items.index')->with('success', 'Inventory item berhasil dibuat.');
    }

    public function edit(InventoryItem $inventoryItem)
    {
        return view('backend.inventory-items.edit', [
            'judul' => 'Edit Inventory Item',
            'item' => $inventoryItem,
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'units' => self::UNITS,
        ]);
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $inventoryItem->update($this->validated($request, $inventoryItem));

        return redirect()->route('backend.inventory-items.index')->with('success', 'Inventory item berhasil diperbarui.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();

        return redirect()->route('backend.inventory-items.index')->with('success', 'Inventory item berhasil dihapus.');
    }

    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        $validated = $request->validate([
            'division_id' => ['nullable', 'exists:divisions,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items', 'code')->ignore($item)],
            'item_type' => ['required', Rule::in(InventoryItem::TYPES)],
            'base_unit' => ['required', Rule::in(self::UNITS)],
            'purchase_unit' => ['nullable', Rule::in(self::UNITS)],
            'conversion_value' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'default_supplier_name' => ['nullable', 'string', 'max:255'],
            'last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['lead_time_days'] = (int) ($validated['lead_time_days'] ?? 0);

        return $validated;
    }
}
