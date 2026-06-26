<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DivisionDailyOperation;
use App\Models\MaterialRequest;
use App\Models\ProductionRecord;
use App\Models\StockOpname;
use App\Models\WasteRecord;
use Illuminate\Http\Request;

class DailyOperationController extends Controller
{
    public function index()
    {
        return view('backend.daily-operations.index', [
            'judul' => 'Daily Operations',
            'operations' => DivisionDailyOperation::with(['outlet', 'division', 'opener', 'submitter', 'approver'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(DivisionDailyOperation $dailyOperation)
    {
        return view('backend.daily-operations.show', [
            'judul' => 'Detail Daily Operation',
            'operation' => $dailyOperation->load(['outlet', 'division', 'opener', 'submitter', 'approver']),
            'summary' => $this->summary($dailyOperation),
        ]);
    }

    public function approve(Request $request, DivisionDailyOperation $dailyOperation)
    {
        if ($dailyOperation->status !== 'submitted') {
            return back()->with('error', 'Hanya daily operation submitted yang bisa diapprove.');
        }

        $dailyOperation->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Daily operation approved. Stok belum berubah.');
    }

    private function summary(DivisionDailyOperation $operation): array
    {
        $id = $operation->id;

        return [
            'materialRequests' => MaterialRequest::with('items')->where('division_daily_operation_id', $id)->latest()->get(),
            'productionRecords' => ProductionRecord::with('items')->where('division_daily_operation_id', $id)->latest()->get(),
            'wasteRecords' => WasteRecord::with('items')->where('division_daily_operation_id', $id)->latest()->get(),
            'stockOpnames' => StockOpname::with('items')->where('division_daily_operation_id', $id)->latest()->get(),
        ];
    }
}
