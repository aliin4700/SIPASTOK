<?php

namespace App\Console\Commands;

use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Recipe;
use App\Services\Inventory\EsbReportService;
use Illuminate\Console\Command;
use Throwable;

class SyncEsbMasterCommand extends Command
{
    protected $signature = 'sipastok:sync-esb-master
        {--sales-file= : Path file Sales Material Usage Report (.xlsx)}
        {--bom-file= : Path file Bill Of Material Report (.xlsx)}
        {--dry-run : Simulasi sinkronisasi tanpa menulis ke database}';

    protected $description = 'Sinkronisasi master item, nama ESB, satuan dasar, dan unit_cost (termasuk kalkulasi otomatis cost WIP dari BOM) dari laporan ESB POS';

    public function handle(EsbReportService $esbReportService): int
    {
        $salesPath = $this->resolveFilePath(
            (string) ($this->option('sales-file') ?: storage_path('app/esb/Sales Material Usage Report_MJCTADMINERP_17957694617175.xlsx'))
        );
        $bomPath = $this->resolveFilePath(
            (string) ($this->option('bom-file') ?: storage_path('app/esb/Bill Of Material Report_MJCTADMINERP_17957676849666.xlsx'))
        );

        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($salesPath) && ! is_file($bomPath)) {
            $this->error('Tidak ada file ESB yang ditemukan. Periksa path --sales-file atau --bom-file.');

            return self::FAILURE;
        }

        // Katalog tergabung per normalized item name
        $catalog = [];
        $bomFormulas = [];
        $branchNames = [];

        if (is_file($salesPath)) {
            $this->info('Membaca Sales Material Usage: ' . $salesPath);
            try {
                $salesParsed = $esbReportService->parseSalesMaterialUsage($salesPath);
                foreach ($salesParsed['rows'] as $row) {
                    if ($row['branch'] !== '') {
                        $branchNames[$row['branch']] = true;
                    }

                    $key = $esbReportService->normalizeKey($row['product_name']);
                    if ($key === '') {
                        continue;
                    }

                    $qty = (float) $row['qty'];
                    $amount = (float) $row['amount'];
                    $unitCost = ($qty > 0 && $amount > 0) ? round($amount / $qty, 4) : 0.0;

                    $this->mergeCatalogEntry($catalog, $key, [
                        'esb_name' => $row['product_name'],
                        'esb_code' => $row['product_code'],
                        'item_type' => $esbReportService->mapEsbCategoryToItemType($row['category'], $row['product_name']),
                        'unit' => $esbReportService->normalizeUnit($row['unit']),
                        'unit_cost' => $unitCost,
                    ]);
                }
            } catch (Throwable $e) {
                $this->error('Gagal membaca Sales Material Usage: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        if (is_file($bomPath)) {
            $this->info('Membaca Bill Of Material: ' . $bomPath);
            try {
                $bomParsed = $esbReportService->parseBillOfMaterial($bomPath);
                $bomFormulas = $bomParsed['formulas'];

                foreach ($bomFormulas as $formula) {
                    $typeNorm = $esbReportService->normalizeKey($formula['formula_type']);

                    // 1. Daftarkan Finished/WIP Output untuk formula Assembly
                    if ($typeNorm === 'ASSEMBLY' && $formula['finished_product_name'] !== '') {
                        $outKey = $esbReportService->normalizeKey($formula['finished_product_name']);
                        $outQty = (float) $formula['output_qty'];
                        $bomCost = (float) ($formula['bom_cost_total'] ?? 0);
                        $outCost = ($outQty > 0 && $bomCost > 0) ? round($bomCost / $outQty, 4) : 0.0;

                        $this->mergeCatalogEntry($catalog, $outKey, [
                            'esb_name' => $formula['finished_product_name'],
                            'esb_code' => $formula['finished_product_code'],
                            'item_type' => 'wip',
                            'unit' => $esbReportService->normalizeUnit($formula['output_unit']),
                            'unit_cost' => $outCost,
                        ]);
                    }

                    // 2. Daftarkan seluruh komponen material di BOM
                    foreach ($formula['materials'] as $mat) {
                        $matKey = $esbReportService->normalizeKey($mat['name']);
                        if ($matKey === '') {
                            continue;
                        }

                        $mQty = (float) $mat['qty'];
                        $mCost = (float) ($mat['bom_cost_total'] ?? 0);
                        $unitCost = ($mQty > 0 && $mCost > 0) ? round($mCost / $mQty, 4) : 0.0;
                        $inferredType = str_starts_with($matKey, 'WIP ') ? 'wip' : 'raw_material';

                        $this->mergeCatalogEntry($catalog, $matKey, [
                            'esb_name' => $mat['name'],
                            'esb_code' => $mat['code'],
                            'item_type' => $inferredType,
                            'unit' => $esbReportService->normalizeUnit($mat['unit']),
                            'unit_cost' => $unitCost,
                        ]);
                    }
                }
            } catch (Throwable $e) {
                $this->error('Gagal membaca Bill Of Material: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        // Pemetaan esb_branch_name pada outlet aktif jika belum terisi
        $defaultBranch = array_key_first($branchNames) ?: 'Medja Cerita';
        if (! $dryRun) {
            Outlet::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('esb_branch_name')->orWhere('esb_branch_name', '');
                })
                ->update(['esb_branch_name' => $defaultBranch]);
        }

        $defaultDivisionId = Division::where('is_active', true)->orderBy('id')->value('id');
        $existingItems = InventoryItem::all();
        [$byName, $byCode] = $esbReportService->buildItemLookup($existingItems);

        $stats = [
            'catalog_total' => count($catalog),
            'matched_updated' => 0,
            'created_new' => 0,
            'cost_filled_direct' => 0,
            'wip_cost_calculated' => 0,
            'total_with_cost' => 0,
        ];

        foreach ($catalog as $normKey => $entry) {
            $item = $esbReportService->findMatchingItem($entry['esb_name'], $entry['esb_code'], $byName, $byCode);

            if ($item) {
                $updates = [
                    'esb_item_name' => $entry['esb_name'],
                ];

                if ($entry['esb_code'] !== '' && empty($item->esb_item_code)) {
                    $updates['esb_item_code'] = $entry['esb_code'];
                }

                if ($entry['unit'] !== '' && (empty($item->base_unit) || $esbReportService->normalizeUnit($item->base_unit) === $entry['unit'])) {
                    $updates['base_unit'] = $entry['unit'];
                }

                if ((float) $item->unit_conversion_factor <= 0) {
                    $updates['unit_conversion_factor'] = 1;
                }

                if ($entry['unit_cost'] > 0) {
                    $updates['unit_cost'] = $entry['unit_cost'];
                    if ((float) ($item->last_purchase_price ?? 0) <= 0) {
                        $updates['last_purchase_price'] = round($entry['unit_cost'], 2);
                    }
                    $stats['cost_filled_direct']++;
                }

                if (! $dryRun) {
                    $item->update($updates);
                } else {
                    $item->fill($updates);
                }

                $stats['matched_updated']++;
            } else {
                $prefix = match ($entry['item_type']) {
                    'wip' => 'ESB-WIP-',
                    'packaging' => 'ESB-PKG-',
                    'finished_goods' => 'ESB-FG-',
                    default => 'ESB-RM-',
                };

                $code = $this->generateUniqueCode($prefix, $normKey);

                $payload = [
                    'division_id' => $defaultDivisionId,
                    'name' => $entry['esb_name'],
                    'code' => $code,
                    'esb_item_code' => $entry['esb_code'] !== '' ? $entry['esb_code'] : null,
                    'esb_item_name' => $entry['esb_name'],
                    'item_type' => $entry['item_type'],
                    'base_unit' => $entry['unit'] !== '' ? $entry['unit'] : 'PCS',
                    'purchase_unit' => $entry['unit'] !== '' ? $entry['unit'] : 'PCS',
                    'conversion_value' => 1,
                    'unit_conversion_factor' => 1,
                    'unit_cost' => $entry['unit_cost'],
                    'last_purchase_price' => $entry['unit_cost'] > 0 ? round($entry['unit_cost'], 2) : 0,
                    'min_stock' => 0,
                    'is_active' => true,
                    'notes' => 'Disinkronkan otomatis dari laporan ESB POS',
                ];

                if ($entry['unit_cost'] > 0) {
                    $stats['cost_filled_direct']++;
                }

                if (! $dryRun) {
                    $item = InventoryItem::create($payload);
                } else {
                    $item = new InventoryItem($payload);
                }

                $byName[$normKey] = $item;
                $stats['created_new']++;
            }
        }

        // Tahap 2: Kalkulasi otomatis unit_cost untuk WIP yang masih 0 dari BOM (multi-pass untuk nested WIP)
        if (! $dryRun) {
            $allItems = InventoryItem::all();
            [$byNameFresh, $byCodeFresh] = $esbReportService->buildItemLookup($allItems);

            // Sumber formula: dari file BOM ESB + dari tabel recipes yang sudah ada
            $bomSources = [];
            foreach ($bomFormulas as $formula) {
                if ($esbReportService->normalizeKey($formula['formula_type']) !== 'ASSEMBLY') {
                    continue;
                }
                $outItem = $esbReportService->findMatchingItem(
                    $formula['finished_product_name'],
                    $formula['finished_product_code'],
                    $byNameFresh,
                    $byCodeFresh
                );
                if (! $outItem || (float) $formula['output_qty'] <= 0) {
                    continue;
                }

                $components = [];
                foreach ($formula['materials'] as $mat) {
                    $matItem = $esbReportService->findMatchingItem($mat['name'], $mat['code'], $byNameFresh, $byCodeFresh);
                    if ($matItem) {
                        $components[] = [
                            'item_id' => $matItem->id,
                            'qty' => (float) $mat['qty'],
                        ];
                    }
                }

                if ($components !== []) {
                    $bomSources[$outItem->id] = [
                        'output_qty' => (float) $formula['output_qty'],
                        'components' => $components,
                    ];
                }
            }

            // Tambahkan juga dari tabel recipes jika ada
            $dbRecipes = Recipe::with('items')->whereNotNull('output_inventory_item_id')->where('is_active', true)->get();
            foreach ($dbRecipes as $recipe) {
                if (isset($bomSources[$recipe->output_inventory_item_id]) || (float) $recipe->output_qty <= 0) {
                    continue;
                }
                $components = $recipe->items->map(fn ($ri) => [
                    'item_id' => $ri->inventory_item_id,
                    'qty' => (float) $ri->qty,
                ])->all();
                if ($components !== []) {
                    $bomSources[$recipe->output_inventory_item_id] = [
                        'output_qty' => (float) $recipe->output_qty,
                        'components' => $components,
                    ];
                }
            }

            $itemById = $allItems->keyBy('id');

            for ($pass = 0; $pass < 3; $pass++) {
                $updatedInPass = 0;
                foreach ($bomSources as $outputItemId => $bomDef) {
                    $outItem = $itemById->get($outputItemId);
                    if (! $outItem || (float) $outItem->unit_cost > 0) {
                        continue;
                    }

                    $totalMaterialCost = 0.0;
                    foreach ($bomDef['components'] as $comp) {
                        $compItem = $itemById->get($comp['item_id']);
                        $compCost = $compItem ? (float) $compItem->unit_cost : 0.0;
                        $totalMaterialCost += ($comp['qty'] * $compCost);
                    }

                    if ($totalMaterialCost > 0 && $bomDef['output_qty'] > 0) {
                        $computedUnitCost = round($totalMaterialCost / $bomDef['output_qty'], 4);
                        $outItem->update([
                            'unit_cost' => $computedUnitCost,
                            'last_purchase_price' => (float) ($outItem->last_purchase_price ?? 0) > 0
                                ? $outItem->last_purchase_price
                                : round($computedUnitCost, 2),
                        ]);
                        $outItem->unit_cost = $computedUnitCost;
                        $stats['wip_cost_calculated']++;
                        $updatedInPass++;
                    }
                }
                if ($updatedInPass === 0) {
                    break;
                }
            }

            $stats['total_with_cost'] = InventoryItem::where('unit_cost', '>', 0)->count();
        }

        $this->newLine();
        $this->info($dryRun ? '=== RINGKASAN DRY-RUN SINKRONISASI MASTER ESB ===' : '=== RINGKASAN SINKRONISASI MASTER ESB ===');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total item unik di file ESB', $stats['catalog_total']],
                ['Master item cocok & diperbarui', $stats['matched_updated']],
                ['Master item baru ditambahkan', $stats['created_new']],
                ['Item terisi unit_cost langsung dari ESB', $stats['cost_filled_direct']],
                ['Item WIP terhitung unit_cost dari BOM', $stats['wip_cost_calculated']],
                ['Total master item dengan unit_cost > 0', $stats['total_with_cost']],
            ]
        );

        if (! $dryRun) {
            $samples = InventoryItem::where('unit_cost', '>', 0)
                ->whereNotNull('esb_item_name')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(['code', 'name', 'item_type', 'base_unit', 'unit_cost']);

            if ($samples->isNotEmpty()) {
                $this->info('Contoh 10 item yang sudah terisi unit_cost:');
                $this->table(
                    ['Kode', 'Nama Item', 'Tipe', 'Satuan', 'Unit Cost (Rp)'],
                    $samples->map(fn ($i) => [
                        $i->code,
                        $i->name,
                        $i->item_type,
                        $i->base_unit,
                        number_format((float) $i->unit_cost, 2, ',', '.'),
                    ])->all()
                );
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @param  array<string, mixed>  $incoming
     */
    private function mergeCatalogEntry(array &$catalog, string $key, array $incoming): void
    {
        if (! isset($catalog[$key])) {
            $catalog[$key] = $incoming;

            return;
        }

        if ($catalog[$key]['esb_code'] === '' && $incoming['esb_code'] !== '') {
            $catalog[$key]['esb_code'] = $incoming['esb_code'];
        }

        if ($incoming['item_type'] === 'wip') {
            $catalog[$key]['item_type'] = 'wip';
        } elseif ($catalog[$key]['item_type'] === 'raw_material' && $incoming['item_type'] !== 'raw_material') {
            $catalog[$key]['item_type'] = $incoming['item_type'];
        }

        if ($catalog[$key]['unit'] === '' && $incoming['unit'] !== '') {
            $catalog[$key]['unit'] = $incoming['unit'];
        }

        if ((float) $incoming['unit_cost'] > 0 && (float) $catalog[$key]['unit_cost'] <= 0) {
            $catalog[$key]['unit_cost'] = $incoming['unit_cost'];
        }
    }

    private function generateUniqueCode(string $prefix, string $normKey): string
    {
        $base = $prefix . strtoupper(substr(md5($normKey), 0, 8));
        $candidate = $base;
        $counter = 1;

        while (InventoryItem::where('code', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function resolveFilePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $baseCandidate = base_path($path);
        if (is_file($baseCandidate)) {
            return $baseCandidate;
        }

        $storageCandidate = storage_path('app/esb/' . ltrim($path, '/\\'));
        if (is_file($storageCandidate)) {
            return $storageCandidate;
        }

        return $path;
    }
}
