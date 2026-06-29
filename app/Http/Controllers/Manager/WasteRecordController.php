<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\WasteRecord;
use App\Models\WasteRecordItem;

class WasteRecordController extends Controller
{
    public function index()
    {
        return view('manager.waste-records.index', [
            'records' => WasteRecord::with(['outlet', 'division', 'recorder', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(WasteRecord $wasteRecord)
    {
        $wasteRecord->load(['outlet', 'division', 'recorder', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', WasteRecordItem::class)
            ->whereIn('reference_id', $wasteRecord->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('manager.waste-records.show', [
            'wasteRecord' => $wasteRecord,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }
}
