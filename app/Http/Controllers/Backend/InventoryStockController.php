<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Outlet;
use Illuminate\Http\Request;

class InventoryStockController extends Controller
{
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
            'available_stock' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
        ]);

        $inventoryStock->update($data);

        return redirect()->route('backend.inventory-stocks.index')->with('success', 'Stok outlet berhasil diperbarui.');
    }
}
