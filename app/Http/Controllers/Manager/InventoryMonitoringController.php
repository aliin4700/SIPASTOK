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
use App\Models\StockOpnameItem;
use App\Models\WasteRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryMonitoringController extends Controller
{
    public function dashboard(Request $request)
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $divisions = Division::where('is_active', true)->orderBy('name')->get();

        $selectedOutletId = $request->filled('outlet_id') ? $request->integer('outlet_id') : null;
        $selectedOutlet = $selectedOutletId ? $outlets->firstWhere('id', $selectedOutletId) : null;

        $latestOpnameDate = StockOpname::when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId))
            ->max('opname_date') ?? StockOpname::max('opname_date');

        $defaultMonth = $latestOpnameDate
            ? Carbon::parse($latestOpnameDate)->format('Y-m')
            : now()->format('Y-m');

        $selectedMonth = $request->filled('month') ? (string) $request->input('month') : $defaultMonth;
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = $defaultMonth;
        }
        [$year, $month] = array_map('intval', explode('-', $selectedMonth));

        // 1. Menunggu Persetujuan (pendingOpnamesCount & 5 dokumen terbaru)
        $pendingQuery = StockOpname::with(['outlet', 'division', 'counter', 'items'])
            ->where('status', 'submitted')
            ->when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId));

        $pendingOpnamesCount = (clone $pendingQuery)->count();
        $pendingOpnames = (clone $pendingQuery)
            ->latest('opname_date')
            ->latest('id')
            ->limit(5)
            ->get();

        // 2 & 3. Rata-rata Akurasi Stok (IRA %) & Total Nilai Selisih (Rp) pada periode terpilih
        $periodOpnames = StockOpname::with(['items', 'outlet', 'division'])
            ->whereYear('opname_date', $year)
            ->whereMonth('opname_date', $month)
            ->when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId))
            ->get();

        $reviewedOpnames = $periodOpnames->where('status', 'reviewed');
        $totalSessionsPeriod = $periodOpnames->count();

        $totalItemsCounted = 0;
        $totalItemsMatched = 0;
        foreach ($periodOpnames as $opname) {
            foreach ($opname->items as $opItem) {
                $totalItemsCounted++;
                if (abs((float) $opItem->difference_qty) < 0.0001) {
                    $totalItemsMatched++;
                }
            }
        }

        $iraPercentage = $totalItemsCounted > 0
            ? ($totalItemsMatched / $totalItemsCounted) * 100.0
            : 100.0;

        $totalVarianceValue = (float) $reviewedOpnames->sum(function (StockOpname $opname) {
            $headerVal = (float) $opname->total_variance_value;
            return abs($headerVal) > 0.0001 ? $headerVal : (float) $opname->items->sum('variance_value');
        });

        // 4. Peringatan Stok Minimum (lowStockCount & 5 item paling kritis)
        $lowStockCollection = InventoryStock::with(['item.division', 'outlet'])
            ->when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId))
            ->whereHas('item', fn ($q) => $q->where('is_active', true))
            ->get()
            ->filter(fn (InventoryStock $stock) => $stock->item && (float) $stock->current_stock <= (float) $stock->item->min_stock)
            ->sortBy(fn (InventoryStock $stock) => (float) $stock->current_stock - (float) $stock->item->min_stock)
            ->values();

        $lowStockCount = $lowStockCollection->count();
        $lowStockItems = $lowStockCollection->take(5);

        // 5. Status Operasional Hari Ini per Divisi
        $todayOpsRecords = DivisionDailyOperation::with(['outlet', 'division', 'opener', 'submitter', 'approver'])
            ->whereDate('operation_date', today())
            ->when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId))
            ->get();

        $todayOperations = $divisions->map(function (Division $division) use ($todayOpsRecords, $selectedOutlet) {
            $opsForDiv = $todayOpsRecords->where('division_id', $division->id);
            $latestOp = $opsForDiv->sortByDesc('id')->first();

            return [
                'division' => $division,
                'outlet_name' => $latestOp?->outlet?->name ?? ($selectedOutlet?->name ?? 'Semua Outlet'),
                'operation' => $latestOp,
                'status' => $latestOp?->status ?? 'not_opened',
                'active_outlets_count' => $opsForDiv->count(),
            ];
        });

        // 6. Top 5 Item dengan Selisih Terbesar & Catatan Waste Terbaru
        $topVarianceItems = StockOpnameItem::with(['item.division', 'stockOpname.outlet', 'stockOpname.division'])
            ->whereHas('stockOpname', function ($q) use ($year, $month, $selectedOutletId) {
                $q->whereYear('opname_date', $year)
                    ->whereMonth('opname_date', $month)
                    ->when($selectedOutletId, fn ($inner) => $inner->where('outlet_id', $selectedOutletId));
            })
            ->whereRaw('ABS(difference_qty) >= 0.01')
            ->orderByRaw('ABS(variance_value) DESC, ABS(difference_qty) DESC')
            ->limit(5)
            ->get();

        $recentWaste = WasteRecord::with(['outlet', 'division', 'recorder', 'items.item'])
            ->when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId))
            ->latest('waste_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('manager.dashboard', [
            'title' => 'Panel Monitoring Operasional & Stok Opname — Medja Cerita',
            'outlets' => $outlets,
            'divisions' => $divisions,
            'selectedOutletId' => $selectedOutletId,
            'selectedOutlet' => $selectedOutlet,
            'selectedMonth' => $selectedMonth,
            'pendingOpnamesCount' => $pendingOpnamesCount,
            'pendingOpnames' => $pendingOpnames,
            'totalSessionsPeriod' => $totalSessionsPeriod,
            'totalItemsCounted' => $totalItemsCounted,
            'totalItemsMatched' => $totalItemsMatched,
            'iraPercentage' => $iraPercentage,
            'totalVarianceValue' => $totalVarianceValue,
            'lowStockCount' => $lowStockCount,
            'lowStockItems' => $lowStockItems,
            'todayOperations' => $todayOperations,
            'topVarianceItems' => $topVarianceItems,
            'recentWaste' => $recentWaste,
        ]);
    }

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

    public function dailyOperationApprove(Request $request, DivisionDailyOperation $dailyOperation)
    {
        if ($dailyOperation->status !== 'submitted') {
            return back()->with('error', 'Hanya daily operation berstatus submitted yang bisa diapprove.');
        }

        $dailyOperation->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Operasional harian berhasil disetujui (Approved).');
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
