<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\Outlet;
use App\Models\UserDivisionAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyOperationController extends Controller
{
    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $operation = $outletId && $divisionId
            ? DivisionDailyOperation::with(['outlet', 'division'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->whereDate('operation_date', today())
                ->first()
            : null;

        return view('staff.daily-operations.index', [
            'message' => $message,
            'operation' => $operation,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
        ]);
    }

    public function open(Request $request)
    {
        [$outletId, $divisionId] = $this->context($request, true);

        if (! $outletId || ! $divisionId) {
            return back()->with('error', 'Akun staff belum memiliki akses outlet/divisi.');
        }

        $operation = DivisionDailyOperation::firstOrCreate(
            [
                'outlet_id' => $outletId,
                'division_id' => $divisionId,
                'operation_date' => today(),
            ],
            [
                'opened_by' => $request->user()->id,
                'opened_at' => now(),
                'status' => 'open',
            ]
        );

        if ($operation->status === 'draft') {
            $operation->update([
                'opened_by' => $request->user()->id,
                'opened_at' => now(),
                'status' => 'open',
            ]);
        }

        return redirect()->route('staff.daily-operations.index', [
            'outlet_id' => $outletId,
            'division_id' => $divisionId,
        ])->with('success', 'Operasional hari ini dibuka.');
    }

    public function submit(Request $request, DivisionDailyOperation $operation)
    {
        abort_unless($this->canAccess($request, $operation->outlet_id, $operation->division_id), 403);

        if ($operation->status !== 'open') {
            return back()->with('error', 'Hanya operasional berstatus open yang bisa disubmit.');
        }

        $operation->update([
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        return back()->with('success', 'Operasional hari ini berhasil disubmit.');
    }

    private function context(Request $request, bool $validateAdminInput = false): array
    {
        $user = $request->user();

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            if ($validateAdminInput) {
                $data = $request->validate([
                    'outlet_id' => ['required', 'exists:outlets,id'],
                    'division_id' => ['required', 'exists:divisions,id'],
                ]);

                return [(int) $data['outlet_id'], (int) $data['division_id'], null];
            }

            return [
                $request->integer('outlet_id') ?: Outlet::where('is_active', true)->value('id'),
                $request->integer('division_id') ?: Division::where('is_active', true)->value('id'),
                null,
            ];
        }

        $access = UserDivisionAccess::with(['outlet', 'division'])
            ->where('user_id', $user->id)
            ->where('can_input_stock', true)
            ->first();

        if (! $access) {
            return [null, null, 'Akun staff belum memiliki akses outlet/divisi.'];
        }

        return [$access->outlet_id, $access->division_id, null];
    }

    private function canAccess(Request $request, int $outletId, int $divisionId): bool
    {
        return $request->user()->hasAnyRole(['admin', 'super_admin'])
            || $request->user()->hasDivisionAccess($outletId, $divisionId, 'can_input_stock');
    }
}
