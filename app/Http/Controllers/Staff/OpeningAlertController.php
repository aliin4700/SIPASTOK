<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\UserDivisionAccess;
use Illuminate\Http\Request;

class OpeningAlertController extends Controller
{
    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $stocks = collect();
        if ($outletId && $divisionId) {
            $stocks = InventoryStock::with(['item.division', 'outlet'])
                ->where('outlet_id', $outletId)
                ->whereHas('item', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('division_id', $divisionId)
                    ->whereIn('item_type', ['wip', 'raw_material', 'packaging']))
                ->get();
        }

        $alerts = $stocks->map(function ($stock) {
            $item = $stock->item;
            $current = (float) $stock->current_stock;
            $minimum = (float) $item->min_stock;
            $category = null;

            if ($item->item_type === 'wip' && $current <= 0) {
                $category = 'WIP_EMPTY';
            } elseif ($item->item_type === 'wip' && $current <= $minimum) {
                $category = 'WIP_LOW';
            } elseif ($item->item_type === 'raw_material' && $current <= 0) {
                $category = 'RAW_EMPTY';
            } elseif ($item->item_type === 'raw_material' && $current <= $minimum) {
                $category = 'RAW_LOW';
            } elseif ($item->item_type === 'packaging' && $current <= 0) {
                $category = 'PACKAGING_EMPTY';
            } elseif ($item->item_type === 'packaging' && $current <= $minimum) {
                $category = 'PACKAGING_LOW';
            }

            if (! $category) {
                return null;
            }

            return [
                'category' => $category,
                'stock' => $stock,
                'recommendation' => str_contains($category, 'WIP')
                    ? (str_contains($category, 'EMPTY') ? 'Urgent production' : 'Recommended production')
                    : (str_contains($category, 'EMPTY') ? 'Needs purchase request' : 'Monitor / prepare purchase request'),
            ];
        })->filter()->values();

        return view('staff.opening-alerts.index', [
            'message' => $message,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
            'wipAlerts' => $alerts->filter(fn ($alert) => str_starts_with($alert['category'], 'WIP')),
            'rawAlerts' => $alerts->filter(fn ($alert) => str_starts_with($alert['category'], 'RAW')),
            'packagingAlerts' => $alerts->filter(fn ($alert) => str_starts_with($alert['category'], 'PACKAGING')),
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

        $access = UserDivisionAccess::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('can_input_stock', true)
                    ->orWhere('can_input_waste', true)
                    ->orWhere('can_request_order', true);
            })
            ->first();

        if (! $access) {
            return [null, null, 'Akun staff belum memiliki akses outlet/divisi.'];
        }

        return [$access->outlet_id, $access->division_id, null];
    }
}
