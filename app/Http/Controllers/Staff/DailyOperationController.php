<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\StockOpname;
use App\Models\UserDivisionAccess;
use App\Models\WasteRecord;
use Illuminate\Http\Request;

class DailyOperationController extends Controller
{
    private bool $requireStockOpnameBeforeSubmit = true;

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

        return $this->submitOperation($request, $operation);
    }

    public function closingSummary(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $operation = $outletId && $divisionId
            ? DivisionDailyOperation::with(['outlet', 'division', 'opener', 'submitter'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->whereDate('operation_date', today())
                ->first()
            : null;

        return view('staff.daily-operations.closing-summary', [
            'message' => $message,
            'operation' => $operation,
            'summary' => $operation ? $this->summary($operation) : null,
            'requireStockOpnameBeforeSubmit' => $this->requireStockOpnameBeforeSubmit,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
        ]);
    }

    public function submitClosing(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request, true);
        if ($message || ! $outletId || ! $divisionId) {
            return back()->with('error', 'Akun staff belum memiliki akses outlet/divisi.');
        }

        $operation = DivisionDailyOperation::where('outlet_id', $outletId)
            ->where('division_id', $divisionId)
            ->whereDate('operation_date', today())
            ->firstOrFail();

        abort_unless($this->canAccess($request, $operation->outlet_id, $operation->division_id), 403);

        return $this->submitOperation($request, $operation);
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

    private function submitOperation(Request $request, DivisionDailyOperation $operation)
    {
        if ($operation->status !== 'open') {
            return back()->with('error', 'Hanya operasional berstatus open yang bisa disubmit.');
        }

        $summary = $this->summary($operation);
        if ($this->requireStockOpnameBeforeSubmit && $summary['stockOpnamesReady']->isEmpty()) {
            return back()->with('error', 'Closing Stock Opname submitted/reviewed wajib ada sebelum tutup operasional.');
        }

        $data = $request->validate([
            'closing_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $operation->update([
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
            'status' => 'submitted',
            'notes' => $data['closing_notes'] ?? $operation->notes,
        ]);

        return redirect()->route('staff.daily-operations.closing-summary', [
            'outlet_id' => $operation->outlet_id,
            'division_id' => $operation->division_id,
        ])->with('success', 'Operasional hari ini berhasil ditutup/submitted. Stok belum berubah.');
    }

    private function summary(DivisionDailyOperation $operation): array
    {
        $id = $operation->id;

        $materialRequests = MaterialRequest::with('items')->where('division_daily_operation_id', $id)->latest()->get();
        $productionRecords = ProductionRecord::with('items')->where('division_daily_operation_id', $id)->latest()->get();
        $wasteRecords = WasteRecord::with('items')->where('division_daily_operation_id', $id)->latest()->get();
        $stockOpnames = StockOpname::with('items')->where('division_daily_operation_id', $id)->latest()->get();

        return [
            'materialRequests' => $materialRequests,
            'productionRecords' => $productionRecords,
            'wasteRecords' => $wasteRecords,
            'stockOpnames' => $stockOpnames,
            'materialRequestsSubmitted' => $materialRequests->where('status', 'submitted'),
            'productionRecordsSubmitted' => $productionRecords->where('status', 'submitted'),
            'wasteRecordsSubmitted' => $wasteRecords->where('status', 'submitted'),
            'stockOpnamesReady' => $stockOpnames->whereIn('status', ['submitted', 'reviewed']),
        ];
    }
}
