<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;

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
        return view('manager.material-requests.show', [
            'materialRequest' => $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']),
        ]);
    }
}
