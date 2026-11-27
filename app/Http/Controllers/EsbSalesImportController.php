<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\EsbSalesImport;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Services\Inventory\EsbReportService;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class EsbSalesImportController extends Controller
{
    public function __construct(
        private EsbReportService $esbReportService,
        private StockMovementService $stockMovementService
    ) {
    }

    public function index(Request $request)
    {
        $context = $this->panelContext($request);

        return view('esb-imports.index', [
            ...$context,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'imports' => EsbSalesImport::with(['outlet', 'importer'])
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function preview(Request $request)
    {
        $context = $this->panelContext($request);

        $validated = $request->validate([
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'report_date' => ['required', 'date'],
            'esb_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'esb_file.mimes' => 'File laporan ESB harus berformat .xlsx.',
        ]);

        $file = $request->file('esb_file');
        $outlet = Outlet::findOrFail((int) $validated['outlet_id']);
        $reportDate = $validated['report_date'];
        $fileName = $file->getClientOriginalName();
        $fileHash = hash_file('sha256', $file->getRealPath());

        try {
            $parsed = $this->esbReportService->parseSalesMaterialUsage($file->getRealPath());
            $matched = $this->esbReportService->matchSalesRows($parsed['rows'], $outlet->id);
        } catch (Throwable $e) {
            return redirect()
                ->route($context['routePrefix'] . '.index')
                ->withInput($request->only(['outlet_id', 'report_date']))
                ->with('error', 'Gagal membaca file ESB: ' . $e->getMessage());
        }

        $duplicateImport = EsbSalesImport::where('outlet_id', $outlet->id)
            ->whereDate('report_date', $reportDate)
            ->where('file_hash', $fileHash)
            ->first();

        $previewToken = (string) Str::uuid();
        $reportPeriod = $parsed['meta']['period'] ?? null;

        session()->put("esb_sales_preview.{$previewToken}", [
            'outlet_id' => $outlet->id,
            'outlet_name' => $outlet->name,
            'report_date' => $reportDate,
            'report_period' => $reportPeriod,
            'file_name' => $fileName,
            'file_hash' => $fileHash,
            'rows' => $matched['rows'],
        ]);

        $inventoryItems = InventoryItem::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'base_unit', 'item_type']);

        $matchedRows = array_values(array_filter($matched['rows'], fn (array $r) => $r['status'] === 'matched'));
        $unmatchedRows = array_values(array_filter($matched['rows'], fn (array $r) => $r['status'] === 'unmatched'));

        return view('esb-imports.preview', [
            ...$context,
            'previewToken' => $previewToken,
            'outlet' => $outlet,
            'reportDate' => $reportDate,
            'reportPeriod' => $reportPeriod,
            'fileName' => $fileName,
            'fileHash' => $fileHash,
            'duplicateImport' => $duplicateImport,
            'totalRows' => count($matched['rows']),
            'matchedCount' => $matched['matched_count'],
            'unmatchedCount' => $matched['unmatched_count'],
            'matchedRows' => $matchedRows,
            'unmatchedRows' => $unmatchedRows,
            'inventoryItems' => $inventoryItems,
        ]);
    }

    public function execute(Request $request)
    {
        $context = $this->panelContext($request);

        $validated = $request->validate([
            'preview_token' => ['required', 'string'],
            'include' => ['nullable', 'array'],
            'mappings' => ['nullable', 'array'],
            'mappings.*' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $sessionKey = 'esb_sales_preview.' . $validated['preview_token'];
        $payload = session()->get($sessionKey);

        if (! is_array($payload) || empty($payload['rows'])) {
            return redirect()
                ->route($context['routePrefix'] . '.index')
                ->with('error', 'Sesi preview impor ESB telah kedaluwarsa. Silakan unggah ulang file laporan.');
        }

        $outletId = (int) $payload['outlet_id'];
        $reportDate = (string) $payload['report_date'];
        $fileHash = (string) $payload['file_hash'];
        $fileName = (string) $payload['file_name'];

        $existingBatch = EsbSalesImport::where('outlet_id', $outletId)
            ->whereDate('report_date', $reportDate)
            ->where('file_hash', $fileHash)
            ->first();

        if ($existingBatch) {
            session()->forget($sessionKey);

            return redirect()
                ->route($context['routePrefix'] . '.index')
                ->with('error', "File {$fileName} untuk outlet dan tanggal {$reportDate} sudah pernah diimpor sebelumnya (Batch #{$existingBatch->id}). Pemotongan stok dibatalkan untuk mencegah duplikasi.");
        }

        $includeMap = $validated['include'] ?? [];
        $manualMappings = $validated['mappings'] ?? [];

        $aggregated = [];
        $matchedRowsProcessed = 0;
        $skippedRows = 0;

        foreach ($payload['rows'] as $row) {
            $idx = (string) $row['index'];
            $qty = (float) $row['qty'];

            if ($qty <= 0) {
                $skippedRows++;
                continue;
            }

            $targetItemId = null;

            if ($row['status'] === 'matched') {
                if (! empty($includeMap[$idx])) {
                    $overrideId = isset($manualMappings[$idx]) && $manualMappings[$idx] !== '' ? (int) $manualMappings[$idx] : null;
                    $targetItemId = $overrideId ?: (int) $row['matched_item_id'];
                }
            } else {
                if (isset($manualMappings[$idx]) && $manualMappings[$idx] !== '') {
                    $targetItemId = (int) $manualMappings[$idx];
                }
            }

            if (! $targetItemId) {
                $skippedRows++;
                continue;
            }

            $matchedRowsProcessed++;
            if (! isset($aggregated[$targetItemId])) {
                $aggregated[$targetItemId] = [
                    'qty' => 0.0,
                    'esb_names' => [],
                ];
            }

            $aggregated[$targetItemId]['qty'] += $qty;
            $aggregated[$targetItemId]['esb_names'][] = $row['product_name'];
        }

        if ($aggregated === []) {
            return redirect()
                ->route($context['routePrefix'] . '.index')
                ->with('error', 'Tidak ada item yang dipilih/dipetakan untuk diproses. Impor dibatalkan.');
        }

        try {
            $import = DB::transaction(function () use (
                $outletId,
                $reportDate,
                $payload,
                $fileName,
                $fileHash,
                $matchedRowsProcessed,
                $skippedRows,
                $aggregated,
                $validated
            ) {
                $duplicateCheck = EsbSalesImport::where('outlet_id', $outletId)
                    ->whereDate('report_date', $reportDate)
                    ->where('file_hash', $fileHash)
                    ->lockForUpdate()
                    ->first();

                if ($duplicateCheck) {
                    throw new \RuntimeException("Batch impor yang sama sudah tersimpan (#{$duplicateCheck->id}).");
                }

                $import = EsbSalesImport::create([
                    'outlet_id' => $outletId,
                    'report_date' => $reportDate,
                    'report_period' => $payload['report_period'] ?? null,
                    'file_name' => $fileName,
                    'file_hash' => $fileHash,
                    'total_rows' => count($payload['rows']),
                    'matched_rows' => $matchedRowsProcessed,
                    'processed_items' => count($aggregated),
                    'skipped_rows' => $skippedRows,
                    'imported_by' => auth()->id(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($aggregated as $itemId => $entry) {
                    $esbList = implode(', ', array_slice(array_unique($entry['esb_names']), 0, 5));
                    $note = "Impor Pemakaian ESB ({$reportDate}) - {$fileName} [{$esbList}]";

                    $this->stockMovementService->decreaseStock([
                        'outlet_id' => $outletId,
                        'inventory_item_id' => (int) $itemId,
                        'quantity' => round((float) $entry['qty'], 2),
                        'movement_type' => 'esb_sales_out',
                        'reference_type' => EsbSalesImport::class,
                        'reference_id' => $import->id,
                        'user_id' => auth()->id(),
                        'cap_at_zero' => true,
                        'notes' => $note,
                    ]);
                }

                return $import;
            });
        } catch (Throwable $e) {
            return redirect()
                ->route($context['routePrefix'] . '.index')
                ->with('error', 'Gagal mengeksekusi impor ESB: ' . $e->getMessage());
        }

        session()->forget($sessionKey);

        return redirect()
            ->route($context['routePrefix'] . '.index')
            ->with(
                'success',
                "Impor Pemakaian ESB (Batch #{$import->id}) berhasil! {$import->processed_items} item stok telah dipotong (dari {$import->matched_rows} baris ESB, {$import->skipped_rows} baris dilewati)."
            );
    }

    public function quickCreateItem(Request $request): JsonResponse
    {
        $this->panelContext($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'esb_code' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'item_type' => ['nullable', Rule::in(InventoryItem::TYPES)],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $esbName = trim((string) $validated['name']);
        $esbCode = trim((string) ($validated['esb_code'] ?? ''));
        $unit = $this->esbReportService->normalizeUnit($validated['unit'] ?? 'PCS');
        $itemType = $validated['item_type'] ?? $this->esbReportService->mapEsbCategoryToItemType($validated['category'] ?? '', $esbName);
        $unitCost = round((float) ($validated['unit_cost'] ?? 0), 4);

        $existingItems = InventoryItem::where('is_active', true)->get();
        [$byName, $byCode] = $this->esbReportService->buildItemLookup($existingItems);
        $existing = $this->esbReportService->findMatchingItem($esbName, $esbCode, $byName, $byCode);

        if ($existing) {
            return response()->json([
                'status' => 'existing',
                'message' => 'Item sudah ada di master dan langsung dipetakan.',
                'item' => [
                    'id' => $existing->id,
                    'code' => $existing->code,
                    'name' => $existing->name,
                    'base_unit' => $existing->base_unit,
                    'unit_cost' => (float) $existing->unit_cost,
                ],
            ]);
        }

        $prefix = match ($itemType) {
            'wip' => 'ESB-WIP-',
            'packaging' => 'ESB-PKG-',
            'finished_goods' => 'ESB-FG-',
            default => 'ESB-RM-',
        };

        $normKey = $this->esbReportService->normalizeKey($esbName);
        $baseCode = $prefix . strtoupper(substr(md5($normKey), 0, 8));
        $code = $baseCode;
        $seq = 1;
        while (InventoryItem::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $seq++;
        }

        $defaultDivisionId = Division::where('is_active', true)->orderBy('id')->value('id');

        $item = InventoryItem::create([
            'division_id' => $defaultDivisionId,
            'name' => $esbName,
            'code' => $code,
            'esb_item_code' => $esbCode !== '' ? $esbCode : null,
            'esb_item_name' => $esbName,
            'item_type' => $itemType,
            'base_unit' => $unit,
            'purchase_unit' => $unit,
            'conversion_value' => 1,
            'unit_conversion_factor' => 1,
            'unit_cost' => $unitCost,
            'last_purchase_price' => $unitCost > 0 ? round($unitCost, 2) : 0,
            'min_stock' => 0,
            'is_active' => true,
            'notes' => 'Ditambahkan cepat dari halaman Preview Impor ESB',
        ]);

        return response()->json([
            'status' => 'created',
            'message' => "Item '{$item->name}' berhasil ditambahkan ke master.",
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'base_unit' => $item->base_unit,
                'unit_cost' => (float) $item->unit_cost,
            ],
        ], 201);
    }

    private function panelContext(Request $request): array
    {
        abort_unless($request->user()?->hasAnyRole(['manager', 'admin', 'super_admin']), 403);

        if ($request->routeIs('backend.*')) {
            return [
                'isBackend' => true,
                'layout' => 'backend.v_layouts.app',
                'routePrefix' => 'backend.esb-imports',
                'judul' => 'Impor Pemakaian ESB',
            ];
        }

        if ($request->routeIs('manager.*')) {
            return [
                'isBackend' => false,
                'layout' => 'manager.layouts.app',
                'routePrefix' => 'manager.esb-imports',
                'judul' => 'Impor Pemakaian ESB',
            ];
        }

        abort(403);
    }
}
