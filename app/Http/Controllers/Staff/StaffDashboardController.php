<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\UserDivisionAccess;
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

        if ($outletId && $divisionId) {
            $operation = DivisionDailyOperation::with(['outlet', 'division'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->whereDate('operation_date', today())
                ->first();

            $stocks = InventoryStock::with('item')
                ->where('outlet_id', $outletId)
                ->whereHas('item', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('division_id', $divisionId)
                    ->whereIn('item_type', ['wip', 'raw_material', 'packaging']))
                ->get();

            $counts['wip'] = $this->alertCount($stocks, 'wip');
            $counts['raw'] = $this->alertCount($stocks, 'raw_material');
            $counts['packaging'] = $this->alertCount($stocks, 'packaging');
        }

        return view('staff.dashboard', [
            'message' => $message,
            'outlet' => $outletId ? Outlet::find($outletId) : null,
            'division' => $divisionId ? Division::find($divisionId) : null,
            'operation' => $operation,
            'counts' => $counts,
        ]);
    }

    private function context(Request $request): array
    {
        $user = $request->user();

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return [
                Outlet::where('is_active', true)->value('id'),
                Division::where('is_active', true)->value('id'),
                null,
            ];
        }

        $access = UserDivisionAccess::with(['outlet', 'division'])
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('can_input_stock', true)
                    ->orWhere('can_input_waste', true)
                    ->orWhere('can_request_order', true);
            })
            ->first();

        if (! $access) {
            return [null, null, 'Akun staff belum memiliki akses outlet/divisi. Hubungi admin.'];
        }

        return [$access->outlet_id, $access->division_id, null];
    }

    private function alertCount($stocks, string $type): int
    {
        return $stocks
            ->filter(fn ($stock) => $stock->item?->item_type === $type)
            ->filter(fn ($stock) => (float) $stock->current_stock <= (float) $stock->item->min_stock)
            ->count();
    }
}
