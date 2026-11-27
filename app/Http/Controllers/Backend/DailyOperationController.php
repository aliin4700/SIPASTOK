<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DivisionDailyOperation;
use App\Models\MaterialRequest;
use App\Models\ProductionRecord;
use App\Models\StockOpname;
use App\Models\WasteRecord;
use App\Services\Inventory\OperationalApprovalService;
use Illuminate\Http\Request;
use Throwable;

class DailyOperationController extends Controller
{
    public function __construct(private OperationalApprovalService $operationalApprovalService)
    {
    }
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
        try {
            $counts = $this->operationalApprovalService->approveDailyOperation(
                $dailyOperation,
                $request->user()
            );
        } catch (Throwable $exception) {
            return back()->with('error', 'Approve operasional gagal: ' . $exception->getMessage());
        }

        return back()->with(
            'success',
            "Daily operation disetujui & saldo stok diperbarui (Stok In: +{$counts['material_requests']}, Produksi: +{$counts['productions']}, Waste: -{$counts['wastes']}, Opname: {$counts['stock_opnames']})."
        );
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
