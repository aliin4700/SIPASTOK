<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\StockMovement;

class MaterialRequestController extends Controller
{
    public function index()
    {
        return view('manager.material-requests.index', [
            'requests' => MaterialRequest::with(['outlet', 'division', 'requester', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', MaterialRequestItem::class)
            ->whereIn('reference_id', $materialRequest->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('manager.material-requests.show', [
            'materialRequest' => $materialRequest,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }
}
