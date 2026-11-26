<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryStockController extends Controller
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    public function index(Request $request)
    {
        return view('backend.inventory-stocks.index', [
            'judul' => 'Stok Outlet',
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'stocks' => InventoryStock::with(['outlet', 'item.division'])
                ->when($request->filled('outlet_id'), fn ($query) => $query->where('outlet_id', $request->outlet_id))
                ->latest()
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function edit(InventoryStock $inventoryStock)
    {
        return view('backend.inventory-stocks.edit', [
            'judul' => 'Edit Stok Outlet',
            'stock' => $inventoryStock->load(['outlet', 'item']),
        ]);
    }

    public function update(Request $request, InventoryStock $inventoryStock)
    {
        $data = $request->validate([
            'current_stock' => ['required', 'numeric', 'min:0'],
            'reserved_stock' => ['required', 'numeric', 'min:0'],
            'available_stock' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $inventoryStock, $data) {
            $newCurrent = (float) $data['current_stock'];
            $newReserved = (float) $data['reserved_stock'];

            $this->stockMovementService->adjustStock([
                'outlet_id' => $inventoryStock->outlet_id,
                'inventory_item_id' => $inventoryStock->inventory_item_id,
                'new_stock' => $newCurrent,
                'movement_type' => 'manual_adjustment',
                'reference_type' => InventoryStock::class,
                'reference_id' => $inventoryStock->id,
                'allow_duplicate' => true,
                'user_id' => $request->user()->id,
                'notes' => $data['notes'] ?? 'Penyesuaian stok manual dari panel Backend Admin',
            ]);

            $inventoryStock->fresh()->update([
                'reserved_stock' => $newReserved,
                'available_stock' => isset($data['available_stock']) && $data['available_stock'] !== ''
                    ? (float) $data['available_stock']
                    : ($newCurrent - $newReserved),
                'unit' => $data['unit'] ?: $inventoryStock->unit,
            ]);
        });

        return redirect()->route('backend.inventory-stocks.index')->with('success', 'Stok outlet berhasil diperbarui dan tercatat di riwayat mutasi.');
    }
}
