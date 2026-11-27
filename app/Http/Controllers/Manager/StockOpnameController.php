<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\EsbSalesImport;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\WasteRecord;
use App\Services\Inventory\StockOpnameReviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class StockOpnameController extends Controller
{
    public function __construct(private StockOpnameReviewService $stockOpnameReviewService)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'status' => ['nullable', Rule::in(StockOpname::STATUSES)],
            'month' => ['nullable', 'string'],
        ]);

        $opnames = StockOpname::with(['outlet', 'division', 'counter', 'items'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $outletId) => $q->where('outlet_id', $outletId))
            ->when($filters['division_id'] ?? null, fn ($q, $divisionId) => $q->where('division_id', $divisionId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('opname_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('manager.stock-opnames.index', [
            'opnames' => $opnames,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'statuses' => StockOpname::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load([
            'outlet',
            'division',
            'counter',
            'reviewer',
            'dailyOperation',
            'items' => fn ($q) => $q->with('item')->orderByRaw('ABS(variance_cost) DESC, ABS(variance_value) DESC, id ASC'),
        ]);

        $movementsByItemId = StockMovement::where('reference_type', StockOpnameItem::class)
            ->whereIn('reference_id', $stockOpname->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        $pendingStatuses = ['pending', 'submitted'];
        $pendingRequests = MaterialRequest::where('outlet_id', $stockOpname->outlet_id)
            ->whereDate('request_date', $stockOpname->opname_date)
            ->whereIn('status', $pendingStatuses)
            ->get();
        $pendingProductions = ProductionRecord::where('outlet_id', $stockOpname->outlet_id)
            ->whereDate('production_date', $stockOpname->opname_date)
            ->whereIn('status', $pendingStatuses)
            ->get();
        $pendingWastes = WasteRecord::where('outlet_id', $stockOpname->outlet_id)
            ->whereDate('waste_date', $stockOpname->opname_date)
            ->whereIn('status', $pendingStatuses)
            ->get();
        $hasEsbImport = EsbSalesImport::where('outlet_id', $stockOpname->outlet_id)
            ->whereDate('report_date', $stockOpname->opname_date)
            ->exists();

        return view('manager.stock-opnames.show', [
            'stockOpname' => $stockOpname,
            'movementsByItemId' => $movementsByItemId,
            'pendingRequests' => $pendingRequests,
            'pendingProductions' => $pendingProductions,
            'pendingWastes' => $pendingWastes,
            'hasEsbImport' => $hasEsbImport,
        ]);
    }

    public function review(Request $request, StockOpname $stockOpname)
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.physical_stock' => ['nullable', 'numeric', 'min:0'],
            'items.*.variance_reason' => ['nullable', Rule::in(array_keys(StockOpnameItem::VARIANCE_REASONS))],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $summary = $this->stockOpnameReviewService->approveAndAdjust(
                $stockOpname,
                $request->user(),
                $validated['items'] ?? [],
                $validated['review_notes'] ?? null
            );
        } catch (Throwable $exception) {
            return back()->with('error', 'Review gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Stock opname direview. Adjustment: {$summary['adjusted']}, no-op: {$summary['noop']}, duplicate ignored: {$summary['duplicate']}.");
    }
}
