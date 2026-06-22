<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\WasteRecord;

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
        return view('manager.waste-records.show', [
            'wasteRecord' => $wasteRecord->load(['outlet', 'division', 'recorder', 'dailyOperation', 'items.item']),
        ]);
    }
}
