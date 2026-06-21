<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SalesTarget;

class SalesTargetController extends Controller
{
    public function index()
    {
        return view('manager.sales-targets.index', [
            'title' => 'Sales Targets',
            'salesTargets' => SalesTarget::with(['outlet', 'creator'])->latest()->paginate(15),
        ]);
    }
}
