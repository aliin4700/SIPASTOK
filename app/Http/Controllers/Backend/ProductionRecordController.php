<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProductionRecord;

class ProductionRecordController extends Controller
{
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
        return view('backend.production-records.show', [
            'judul' => 'Detail Production Record',
            'productionRecord' => $productionRecord->load(['outlet', 'division', 'producer', 'dailyOperation', 'items.item']),
        ]);
    }
}
