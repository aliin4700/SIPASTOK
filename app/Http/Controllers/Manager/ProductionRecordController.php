<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ProductionRecord;

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
        return view('manager.production-records.show', [
            'productionRecord' => $productionRecord->load(['outlet', 'division', 'producer', 'dailyOperation', 'items.item']),
        ]);
    }
}
