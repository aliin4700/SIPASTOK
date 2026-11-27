<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Executive Dashboard Admin SIPASTOK — Medja Cerita.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $totalItems = InventoryItem::where('is_active', true)->count();

        $lowStockQuery = InventoryStock::with(['outlet', 'item.division'])
            ->whereHas('item', function ($query) {
                $query->where('is_active', true)
                    ->whereColumn('inventory_stocks.current_stock', '<=', 'inventory_items.min_stock');
            });

        $lowStockCount = (clone $lowStockQuery)->count();
        $criticalStocks = (clone $lowStockQuery)
            ->orderBy('current_stock')
            ->limit(6)
            ->get();

        $pendingOpnamesCount = StockOpname::where('status', 'submitted')->count();
        $reviewedOpnamesCount = StockOpname::where('status', 'reviewed')->count();

        $latestDate = StockOpname::max('opname_date');
        $evalDate = $latestDate ? Carbon::parse($latestDate) : now();
        $evalMonthLabel = $evalDate->format('Y-m');

        $periodOpnames = StockOpname::with('items')
            ->whereYear('opname_date', (int) $evalDate->year)
            ->whereMonth('opname_date', (int) $evalDate->month)
            ->get();

        $totalCounted = 0;
        $totalMatched = 0;
        foreach ($periodOpnames as $opname) {
            foreach ($opname->items as $opItem) {
                $totalCounted++;
                if (abs((float) $opItem->difference_qty) < 0.0001) {
                    $totalMatched++;
                }
            }
        }

        $iraPercentage = $totalCounted > 0 ? ($totalMatched / $totalCounted) * 100.0 : 100.0;

        $totalVarianceValue = (float) DB::table('stock_opname_items as soi')
            ->join('stock_opnames as so', 'so.id', '=', 'soi.stock_opname_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'soi.inventory_item_id')
            ->whereYear('so.opname_date', (int) $evalDate->year)
            ->whereMonth('so.opname_date', (int) $evalDate->month)
            ->whereIn('so.status', ['reviewed', 'approved'])
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(soi.variance_cost, 0), soi.difference_qty * ii.unit_cost)), 0) as total_variance')
            ->value('total_variance');

        $opnameStatusCounts = StockOpname::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $itemsByDivision = Division::withCount(['inventoryItems' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Tren aktivitas opname / mutasi stok 14 hari terakhir (dari tanggal opname/mutasi terbaru atau hari ini).
        $anchorDate = $latestDate && Carbon::parse($latestDate)->lt(now()->subDays(14))
            ? Carbon::parse($latestDate)->startOfDay()
            : now()->startOfDay();
        $startDate = $anchorDate->copy()->subDays(13);

        $opnameDailyCounts = StockOpname::query()
            ->whereBetween('opname_date', [$startDate->toDateString(), $anchorDate->toDateString()])
            ->selectRaw('DATE(opname_date) as op_date, COUNT(*) as total')
            ->groupBy('op_date')
            ->pluck('total', 'op_date');

        $movementDailyCounts = StockMovement::query()
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $anchorDate->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as mv_date, COUNT(*) as total')
            ->groupBy('mv_date')
            ->pluck('total', 'mv_date');

        $activityTrend = collect(CarbonPeriod::create($startDate, $anchorDate))
            ->map(function ($date) use ($opnameDailyCounts, $movementDailyCounts) {
                $key = $date->format('Y-m-d');
                return [
                    'label' => $date->format('d M'),
                    'opnames' => (int) ($opnameDailyCounts[$key] ?? 0),
                    'movements' => (int) ($movementDailyCounts[$key] ?? 0),
                ];
            });

        $latestOpnames = StockOpname::with(['outlet', 'division', 'counter', 'items'])
            ->latest('opname_date')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('backend.v_dashboard.index', [
            'judul' => 'Dashboard SIPASTOK',
            'evalMonthLabel' => $evalMonthLabel,
            'cards' => [
                'totalItems' => $totalItems,
                'totalOutlets' => Outlet::where('is_active', true)->count(),
                'pendingOpnamesCount' => $pendingOpnamesCount,
                'reviewedOpnamesCount' => $reviewedOpnamesCount,
                'iraPercentage' => $iraPercentage,
                'totalVarianceValue' => $totalVarianceValue,
                'lowStockCount' => $lowStockCount,
            ],
            'charts' => [
                'opnameStatus' => [
                    'labels' => $opnameStatusCounts->keys()->map(fn ($status) => ucwords(str_replace('_', ' ', $status)))->values(),
                    'data' => $opnameStatusCounts->values(),
                ],
                'itemsByDivision' => [
                    'labels' => $itemsByDivision->pluck('name')->values(),
                    'data' => $itemsByDivision->pluck('inventory_items_count')->map(fn ($v) => (int) $v)->values(),
                ],
                'activityTrend' => [
                    'labels' => $activityTrend->pluck('label')->values(),
                    'opnames' => $activityTrend->pluck('opnames')->values(),
                    'movements' => $activityTrend->pluck('movements')->values(),
                ],
            ],
            'latestOpnames' => $latestOpnames,
            'criticalStocks' => $criticalStocks,
        ]);
    }
}
