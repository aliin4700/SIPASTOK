<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;

class StockMovementController extends Controller
{
    public function index()
    {
        return view('backend.stock-movements.index', [
            'judul' => 'Stock Movement',
            'movements' => StockMovement::with(['outlet', 'division', 'item', 'creator'])->latest()->paginate(30),
        ]);
    }
}
