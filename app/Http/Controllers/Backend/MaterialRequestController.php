<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\StockMovement;
use App\Services\Inventory\OperationalApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MaterialRequestController extends Controller
{
    public function __construct(private OperationalApprovalService $operationalApprovalService)
    {
    }

    public function index(Request $request)
    {
        return view('backend.material-requests.index', [
            'judul' => 'Daftar Stok In',
            'requests' => MaterialRequest::with(['outlet', 'division', 'requester', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', MaterialRequestItem::class)
            ->whereIn('reference_id', $materialRequest->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('backend.material-requests.show', [
            'judul' => 'Detail Stok In',
            'materialRequest' => $materialRequest,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function issueStock(Request $request, MaterialRequest $materialRequest)
    {
        $mode = $request->input('mode', 'restock_in');

        try {
            $summary = $this->operationalApprovalService->approveMaterialRequest(
                $materialRequest,
                $request->user(),
                $mode
            );
        } catch (Throwable $exception) {
            return back()->with('error', 'Proses stok gagal: ' . $exception->getMessage());
        }

        $label = $mode === 'issue_out' ? 'Pengeluaran bahan (-)' : 'Persetujuan Stok In (+)';
        return back()->with('success', "{$label} berhasil diterapkan ke saldo stok. Diproses: {$summary['processed']} item, duplikat diabaikan: {$summary['duplicate']}.");
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:material_requests,id'],
        ], [
            'ids.required' => 'Pilih minimal satu dokumen Stok In untuk disetujui.',
        ]);

        $records = MaterialRequest::whereIn('id', $validated['ids'])
            ->where('status', 'submitted')
            ->get();

        $approved = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                $this->operationalApprovalService->approveMaterialRequest($record, $request->user(), 'restock_in');
                $approved++;
            } catch (Throwable $e) {
                $errors[] = ($record->request_number ?? '#' . $record->id) . ': ' . $e->getMessage();
            }
        }

        if ($approved === 0 && $errors !== []) {
            return back()->with('error', 'Gagal menyetujui dokumen terpilih: ' . implode(' | ', array_slice($errors, 0, 3)));
        }

        $msg = "Berhasil menyetujui {$approved} dokumen Stok In sekaligus (+ Restock ke stok).";
        if ($errors !== []) {
            $msg .= ' (' . count($errors) . ' dokumen gagal diproses).';
        }

        return back()->with('success', $msg);
    }

    public function updateReceipt(Request $request, MaterialRequest $materialRequest)
    {
        $request->validate(
            ['receipt' => MaterialRequest::RECEIPT_REQUIRED_VALIDATION_RULES],
            MaterialRequest::RECEIPT_VALIDATION_MESSAGES
        );

        $materialRequest->replaceReceipt($request->file('receipt'));

        return back()->with('success', 'Bukti struk / nota berhasil diperbarui.');
    }

    public function deleteReceipt(MaterialRequest $materialRequest)
    {
        $materialRequest->clearReceipt();

        return back()->with('success', 'Bukti struk / nota berhasil dihapus.');
    }

    public function downloadReceipt(MaterialRequest $materialRequest)
    {
        abort_if(
            ! $materialRequest->receipt_path || ! Storage::disk('public')->exists($materialRequest->receipt_path),
            404,
            'File bukti struk tidak ditemukan.'
        );

        return Storage::disk('public')->download(
            $materialRequest->receipt_path,
            $materialRequest->receipt_original_name ?: basename($materialRequest->receipt_path)
        );
    }

    public function destroy(MaterialRequest $materialRequest)
    {
        if (! in_array($materialRequest->status, ['draft', 'cancelled'], true)) {
            return back()->with('error', 'Hanya dokumen Stok In berstatus draft atau cancelled yang boleh dihapus.');
        }

        $materialRequest->delete();

        return redirect()->route('backend.material-requests.index')
            ->with('success', 'Data Stok In beserta file lampiran berhasil dihapus.');
    }

    private function issueQuantity(MaterialRequestItem $item): float
    {
        foreach (['issued_qty', 'approved_qty', 'requested_qty'] as $field) {
            if (is_numeric($item->{$field}) && (float) $item->{$field} > 0) {
                return (float) $item->{$field};
            }
        }

        return 0.0;
    }
}
