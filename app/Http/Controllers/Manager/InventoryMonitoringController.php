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
use App\Services\Inventory\OperationalApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryMonitoringController extends Controller
{
    public function __construct(private OperationalApprovalService $operationalApprovalService)
    {
    }
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

        $totalVarianceValue = (float) DB::table('stock_opname_items as soi')
            ->join('stock_opnames as so', 'so.id', '=', 'soi.stock_opname_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'soi.inventory_item_id')
            ->whereYear('so.opname_date', $year)
            ->whereMonth('so.opname_date', $month)
            ->whereIn('so.status', ['reviewed', 'approved'])
            ->when($selectedOutletId, fn ($q) => $q->where('so.outlet_id', $selectedOutletId))
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(soi.variance_cost, 0), soi.difference_qty * ii.unit_cost)), 0) as total_variance')
            ->value('total_variance');

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
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $allowedOutletIds = [];
        $allowedDivisionIds = [];

        if (! $isAdmin && $user) {
            $allowedOutletIds = $user->outletAccesses()->pluck('outlet_id')->unique()->values()->all();
            $allowedDivisionIds = $user->divisionAccesses()->pluck('division_id')->unique()->values()->all();
        }

        $outlets = Outlet::where('is_active', true)
            ->when(! empty($allowedOutletIds), fn ($query) => $query->whereIn('id', $allowedOutletIds))
            ->orderBy('name')
            ->get();

        $selectedOutletId = $request->filled('outlet_id') ? $request->integer('outlet_id') : null;
        if ($selectedOutletId && ! empty($allowedOutletIds) && ! in_array($selectedOutletId, $allowedOutletIds, true)) {
            $selectedOutletId = null;
        }

        if (! $isAdmin && $user && ! empty($allowedDivisionIds) && $selectedOutletId) {
            $outletDivisionIds = $user->divisionAccesses()
                ->where('outlet_id', $selectedOutletId)
                ->pluck('division_id')
                ->unique()
                ->values()
                ->all();
            if (! empty($outletDivisionIds)) {
                $allowedDivisionIds = $outletDivisionIds;
            }
        }

        $divisions = Division::where('is_active', true)
            ->when(! empty($allowedDivisionIds), fn ($query) => $query->whereIn('id', $allowedDivisionIds))
            ->orderBy('name')
            ->get();

        $selectedDivisionId = $request->filled('division_id') ? $request->integer('division_id') : null;
        if ($selectedDivisionId && ! empty($allowedDivisionIds) && ! in_array($selectedDivisionId, $allowedDivisionIds, true)) {
            $selectedDivisionId = null;
        }

        $search = trim((string) ($request->input('search') ?? $request->input('q') ?? ''));

        $stocks = InventoryStock::with(['outlet', 'item.division'])
            ->when(! empty($allowedOutletIds), fn ($query) => $query->whereIn('outlet_id', $allowedOutletIds))
            ->when($selectedOutletId, fn ($query) => $query->where('outlet_id', $selectedOutletId))
            ->when(! empty($allowedDivisionIds), fn ($query) => $query->whereHas('item', fn ($itemQuery) => $itemQuery->whereIn('division_id', $allowedDivisionIds)))
            ->when($selectedDivisionId, fn ($query) => $query->whereHas('item', fn ($itemQuery) => $itemQuery->where('division_id', $selectedDivisionId)))
            ->when($search !== '', fn ($query) => $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }))
            ->paginate(30)
            ->withQueryString();

        return view('manager.inventory.index', [
            'outlets' => $outlets,
            'divisions' => $divisions,
            'selectedOutletId' => $selectedOutletId,
            'selectedDivisionId' => $selectedDivisionId,
            'search' => $search,
            'stocks' => $stocks,
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
            "Operasional harian berhasil disetujui & stok diperbarui (Stok In: +{$counts['material_requests']}, Produksi: +{$counts['productions']}, Waste: -{$counts['wastes']}, Opname: {$counts['stock_opnames']})."
        );
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
