<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;

class StockOpnameController extends Controller
{
    public function index()
    {
        return view('manager.stock-opnames.index', [
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

        return view('manager.stock-opnames.show', [
            'stockOpname' => $stockOpname,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }
}
