<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\Finance\FinanceSummaryService;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    public function __construct(private FinanceSummaryService $finance)
    {
    }

    public function dashboard(Request $request)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
        ]);

        return view('backend.finance.dashboard', [
            'summary' => $this->finance->summary(
                $filters['date'] ?? null,
                isset($filters['month']) ? (int) $filters['month'] : null,
                isset($filters['year']) ? (int) $filters['year'] : null,
                isset($filters['outlet_id']) ? (int) $filters['outlet_id'] : null,
            ),
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }
}
