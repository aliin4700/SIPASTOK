<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;

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
        return view('manager.stock-opnames.show', [
            'stockOpname' => $stockOpname->load(['outlet', 'division', 'counter', 'reviewer', 'dailyOperation', 'items.item']),
        ]);
    }
}
