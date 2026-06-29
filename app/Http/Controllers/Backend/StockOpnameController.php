<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\Inventory\StockMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class StockOpnameController extends Controller
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    public function index()
    {
        return view('backend.stock-opnames.index', [
            'judul' => 'Stock Opname',
            'opnames' => StockOpname::with(['outlet', 'division', 'counter', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load(['outlet', 'division', 'counter', 'reviewer', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', StockOpnameItem::class)
            ->whereIn('reference_id', $stockOpname->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('backend.stock-opnames.show', [
            'judul' => 'Detail Stock Opname',
            'stockOpname' => $stockOpname,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function review(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'submitted') {
            return back()->with('error', 'Hanya stock opname submitted yang bisa direview.');
        }

        $stockOpname->load(['items.item']);

        if ($stockOpname->items->isEmpty()) {
            return back()->with('error', 'Stock opname belum memiliki item.');
        }

        foreach ($stockOpname->items as $item) {
            if (! $item->item || ! is_numeric($item->physical_stock) || (float) $item->physical_stock < 0) {
                return back()->with('error', 'Item opname tidak valid. Pastikan inventory item ada dan physical stock tidak negatif.');
            }
        }

        try {
            $summary = DB::transaction(function () use ($request, $stockOpname) {
                $summary = ['adjusted' => 0, 'noop' => 0, 'duplicate' => 0];

                foreach ($stockOpname->items as $item) {
                    $result = $this->stockMovementService->adjustStock([
                        'outlet_id' => $stockOpname->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'new_stock' => $item->physical_stock,
                        'movement_type' => 'stock_opname_adjustment',
                        'reference_type' => StockOpnameItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $request->user()->id,
                        'notes' => 'Adjustment from reviewed stock opname ' . ($stockOpname->opname_number ?? $stockOpname->id),
                    ]);

                    if ($result['duplicate']) {
                        $summary['duplicate']++;
                    } elseif ($result['noop']) {
                        $summary['noop']++;
                    } else {
                        $summary['adjusted']++;
                    }
                }

                $stockOpname->update([
                    'status' => 'reviewed',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                return $summary;
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Review gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Stock opname direview. Adjustment: {$summary['adjusted']}, no-op: {$summary['noop']}, duplicate ignored: {$summary['duplicate']}.");
    }
}
