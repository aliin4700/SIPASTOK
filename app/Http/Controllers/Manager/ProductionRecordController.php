<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ProductionRecordItem;
use App\Models\ProductionRecord;
use App\Models\StockMovement;

class ProductionRecordController extends Controller
{
    public function index()
    {
        return view('manager.production-records.index', [
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

        return view('manager.production-records.show', [
            'productionRecord' => $productionRecord,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }
}
