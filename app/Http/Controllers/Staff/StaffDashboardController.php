<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryStock;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\StockOpname;
use App\Models\UserDivisionAccess;
use App\Models\WasteRecord;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $operation = null;
        $counts = [
            'wip' => 0,
            'raw' => 0,
            'packaging' => 0,
        ];
        $shiftStats = [
            'material_requests' => 0,
            'productions' => 0,
            'wastes' => 0,
            'stock_opnames' => 0,
            'opname_ready' => false,
        ];

        if ($outletId && $divisionId) {
            $operation = DivisionDailyOperation::with(['outlet', 'division'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->whereDate('operation_date', today())
                ->first();

            $stocks = InventoryStock::with('item')
                ->where('outlet_id', $outletId)
                ->whereHas('item', fn($query) => $query
                    ->where('is_active', true)
                    ->where('division_id', $divisionId)
                    ->whereIn('item_type', ['wip', 'raw_material', 'packaging']))
                ->get();

            $counts['wip'] = $this->alertCount($stocks, 'wip');
            $counts['raw'] = $this->alertCount($stocks, 'raw_material');
            $counts['packaging'] = $this->alertCount($stocks, 'packaging');

            if ($operation) {
                $shiftStats['material_requests'] = MaterialRequest::where('division_daily_operation_id', $operation->id)->count();
                $shiftStats['productions'] = ProductionRecord::where('division_daily_operation_id', $operation->id)->count();
                $shiftStats['wastes'] = WasteRecord::where('division_daily_operation_id', $operation->id)->count();
                $opnames = StockOpname::where('division_daily_operation_id', $operation->id)->get();
                $shiftStats['stock_opnames'] = $opnames->count();
                $shiftStats['opname_ready'] = $opnames->whereIn('status', ['submitted', 'reviewed'])->isNotEmpty();
            }
        }

        return view('staff.dashboard', [
            'message' => $message,
            'outlet' => $outletId ? Outlet::find($outletId) : null,
            'division' => $divisionId ? Division::find($divisionId) : null,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
            'operation' => $operation,
            'counts' => $counts,
            'shiftStats' => $shiftStats,
        ]);
    }

    private function context(Request $request): array
    {
        $user = $request->user();

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return [
                $request->integer('outlet_id') ?: Outlet::where('is_active', true)->value('id'),
                $request->integer('division_id') ?: Division::where('is_active', true)->value('id'),
                null,
            ];
        }

        $accessQuery = UserDivisionAccess::with(['outlet', 'division'])
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('can_input_stock', true)
                    ->orWhere('can_input_waste', true)
                    ->orWhere('can_request_order', true);
            });

        $accesses = $accessQuery->get();

        if ($accesses->isEmpty()) {
            return [null, null, 'Akun staff belum memiliki akses outlet/divisi. Hubungi admin.'];
        }

        $reqOutlet = $request->integer('outlet_id');
        $reqDivision = $request->integer('division_id');

        $matched = $accesses->first(fn($a) => ($reqOutlet ? (int) $a->outlet_id === $reqOutlet : true)
            && ($reqDivision ? (int) $a->division_id === $reqDivision : true)) ?? $accesses->first();

        return [$matched->outlet_id, $matched->division_id, null];
    }

    private function alertCount($stocks, string $type): int
    {
        return $stocks
            ->filter(fn($stock) => $stock->item?->item_type === $type)
            ->filter(fn($stock) => (float) $stock->current_stock <= (float) $stock->item->min_stock)
            ->count();
    }
}
