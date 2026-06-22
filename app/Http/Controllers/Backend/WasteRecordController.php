<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WasteRecord;

class WasteRecordController extends Controller
{
    public function index()
    {
        return view('backend.waste-records.index', [
            'judul' => 'Waste Record',
            'records' => WasteRecord::with(['outlet', 'division', 'recorder', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(WasteRecord $wasteRecord)
    {
        return view('backend.waste-records.show', [
            'judul' => 'Detail Waste Record',
            'wasteRecord' => $wasteRecord->load(['outlet', 'division', 'recorder', 'dailyOperation', 'items.item']),
        ]);
    }
}
