<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProductionRecordItem;
use App\Models\ProductionRecord;
use App\Models\StockMovement;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductionRecordController extends Controller
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    public function index()
    {
        return view('backend.production-records.index', [
            'judul' => 'Production Record',
            'records' => ProductionRecord::with(['outlet', 'division', 'producer', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(ProductionRecord $productionRecord)
    {
        $productionRecord->load(['outlet', 'division', 'producer', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', ProductionRecordItem::class)
            ->whereIn('reference_id', $productionRecord->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('backend.production-records.show', [
            'judul' => 'Detail Production Record',
            'productionRecord' => $productionRecord,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function applyStock(Request $request, ProductionRecord $productionRecord)
    {
        if ($productionRecord->status !== 'submitted') {
            return back()->with('error', 'Hanya production record submitted yang bisa apply stock.');
        }

        $productionRecord->load(['items.item']);

        if ($productionRecord->items->isEmpty()) {
            return back()->with('error', 'Production record belum memiliki item.');
        }

        foreach ($productionRecord->items as $item) {
            if (! $item->item || ! is_numeric($item->qty) || (float) $item->qty <= 0) {
                return back()->with('error', 'Item produksi tidak valid. Pastikan inventory item ada dan qty lebih dari 0.');
            }
        }

        try {
            $summary = DB::transaction(function () use ($request, $productionRecord) {
                $summary = ['increased' => 0, 'duplicate' => 0];

                foreach ($productionRecord->items as $item) {
                    $result = $this->stockMovementService->increaseStock([
                        'outlet_id' => $productionRecord->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity' => $item->qty,
                        'movement_type' => 'production_in',
                        'reference_type' => ProductionRecordItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $request->user()->id,
                        'notes' => 'Production stock increase from production record ' . ($productionRecord->production_number ?? $productionRecord->id),
                    ]);

                    $result['duplicate'] ? $summary['duplicate']++ : $summary['increased']++;
                }

                $productionRecord->update(['status' => 'applied']);

                return $summary;
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Apply stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Production stock increase applied. Increased: {$summary['increased']}, duplicate ignored: {$summary['duplicate']}.");
    }
}
