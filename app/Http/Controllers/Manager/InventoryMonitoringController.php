<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryStock;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\StockOpname;
use App\Models\WasteRecord;
use Illuminate\Http\Request;

class InventoryMonitoringController extends Controller
{
    public function inventory(Request $request)
    {
        return view('manager.inventory.index', [
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'stocks' => InventoryStock::with(['outlet', 'item.division'])
                ->when($request->filled('outlet_id'), fn ($query) => $query->where('outlet_id', $request->outlet_id))
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function dailyOperations()
    {
        return view('manager.daily-operations.index', [
            'operations' => DivisionDailyOperation::with(['outlet', 'division', 'opener', 'submitter'])
                ->latest()
                ->paginate(30),
        ]);
    }

    public function dailyOperationShow(DivisionDailyOperation $dailyOperation)
    {
        return view('manager.daily-operations.show', [
            'operation' => $dailyOperation->load(['outlet', 'division', 'opener', 'submitter', 'approver']),
            'summary' => $this->summary($dailyOperation),
        ]);
    }

    public function openingAlerts(Request $request)
    {
        $outletId = $request->integer('outlet_id') ?: Outlet::where('is_active', true)->value('id');
        $divisionId = $request->integer('division_id') ?: Division::where('is_active', true)->value('id');

        $alerts = InventoryStock::with(['item.division', 'outlet'])
            ->where('outlet_id', $outletId)
            ->whereHas('item', fn ($query) => $query
                ->where('is_active', true)
                ->where('division_id', $divisionId)
                ->whereIn('item_type', ['wip', 'raw_material', 'packaging']))
            ->get()
            ->filter(fn ($stock) => (float) $stock->current_stock <= (float) $stock->item->min_stock);

        return view('manager.opening-alerts.index', [
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
            'alerts' => $alerts,
        ]);
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
