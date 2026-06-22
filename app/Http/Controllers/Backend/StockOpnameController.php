<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
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
        return view('backend.stock-opnames.show', [
            'judul' => 'Detail Stock Opname',
            'stockOpname' => $stockOpname->load(['outlet', 'division', 'counter', 'reviewer', 'dailyOperation', 'items.item']),
        ]);
    }

    public function review(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'submitted') {
            return back()->with('error', 'Hanya stock opname submitted yang bisa direview.');
        }

        $stockOpname->update([
            'status' => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Stock opname direview. Stok belum diubah.');
    }
}
