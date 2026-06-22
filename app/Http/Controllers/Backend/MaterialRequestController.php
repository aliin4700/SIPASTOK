<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use Illuminate\Http\Request;

class MaterialRequestController extends Controller
{
    public function index(Request $request)
    {
        return view('backend.material-requests.index', [
            'judul' => 'Material Request',
            'requests' => MaterialRequest::with(['outlet', 'division', 'requester', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(MaterialRequest $materialRequest)
    {
        return view('backend.material-requests.show', [
            'judul' => 'Detail Material Request',
            'materialRequest' => $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']),
        ]);
    }
}
