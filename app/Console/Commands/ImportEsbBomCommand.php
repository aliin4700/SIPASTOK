<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Services\Inventory\EsbReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportEsbBomCommand extends Command
{
    protected $signature = 'sipastok:import-esb-bom
        {file? : Path file Bill Of Material Report (.xlsx) dari ESB}
        {--types=Assembly,Menu : Filter Formula Type yang akan disinkronisasi (pisahkan koma)}
        {--dry-run : Simulasi parsing dan pencocokan tanpa menyimpan ke database}';

    protected $description = 'Impor dan sinkronisasi resep/BOM (Assembly WIP & Menu) dari laporan Bill Of Material ESB POS ke tabel recipes & recipe_items';

    public function handle(EsbReportService $esbReportService): int
    {
        $fileArg = $this->argument('file') ?: storage_path('app/esb/Bill Of Material Report_MJCTADMINERP_17957676849666.xlsx');
        $filePath = $this->resolveFilePath($fileArg);

        if (! is_file($filePath)) {
            $this->error("File BOM ESB tidak ditemukan: {$fileArg}");

            return self::FAILURE;
        }

        $allowedTypes = array_values(array_filter(array_map(
            fn (string $t) => $esbReportService->normalizeKey($t),
            explode(',', (string) $this->option('types'))
        )));

        $dryRun = (bool) $this->option('dry-run');

        $this->info('Membaca file BOM ESB: ' . $filePath);

        try {
            $parsed = $esbReportService->parseBillOfMaterial($filePath);
        } catch (Throwable $e) {
            $this->error('Gagal membaca file BOM ESB: ' . $e->getMessage());

            return self::FAILURE;
        }

        $formulas = $parsed['formulas'];
        $items = InventoryItem::where('is_active', true)->get();
        [$byName, $byCode] = $esbReportService->buildItemLookup($items);

        $stats = [
            'total_formulas' => count($formulas),
            'filtered_formulas' => 0,
            'assembly_synced' => 0,
            'menu_synced' => 0,
            'other_synced' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped_no_materials' => 0,
            'output_matched' => 0,
            'material_lines_matched' => 0,
            'material_lines_unmatched' => 0,
        ];

        $unmatchedMaterialNames = [];

        foreach ($formulas as $formula) {
            $typeNorm = $esbReportService->normalizeKey($formula['formula_type']);
            if ($allowedTypes !== [] && ! in_array($typeNorm, $allowedTypes, true)) {
                continue;
            }

            $stats['filtered_formulas']++;

            $outputItem = $esbReportService->findMatchingItem(
                $formula['finished_product_name'],
                $formula['finished_product_code'],
                $byName,
                $byCode
            );

            if ($outputItem) {
                $stats['output_matched']++;
            }

            // Cocokkan komponen bahan dan gabungkan jika ada bahan duplikat dalam satu resep
            $matchedMaterials = [];
            foreach ($formula['materials'] as $mat) {
                $matItem = $esbReportService->findMatchingItem($mat['name'], $mat['code'], $byName, $byCode);
                if (! $matItem) {
                    $stats['material_lines_unmatched']++;
                    $unmatchedMaterialNames[$mat['name']] = ($unmatchedMaterialNames[$mat['name']] ?? 0) + 1;
                    continue;
                }

                $stats['material_lines_matched']++;
                if (! isset($matchedMaterials[$matItem->id])) {
                    $matchedMaterials[$matItem->id] = [
                        'inventory_item_id' => $matItem->id,
                        'qty' => 0.0,
                        'unit' => $mat['unit'] !== '' ? $mat['unit'] : $matItem->base_unit,
                    ];
                }
                $matchedMaterials[$matItem->id]['qty'] += (float) $mat['qty'];
            }

            if ($matchedMaterials === []) {
                $stats['skipped_no_materials']++;
                continue;
            }

            $typePrefix = match ($typeNorm) {
                'ASSEMBLY' => 'ASM',
                'MENU' => 'MNU',
                default => 'BOM',
            };

            $recipeCode = $formula['formula_code'] !== ''
                ? strtoupper($formula['formula_code'])
                : 'ESB-' . $typePrefix . '-' . strtoupper(substr(md5($typeNorm . '|' . $esbReportService->normalizeKey($formula['formula_name']) . '|' . $esbReportService->normalizeKey($formula['finished_product_name'])), 0, 8));

            $notesParts = ["ESB BOM ({$formula['formula_type']})"];
            if ($formula['finished_unit_raw'] !== '') {
                $notesParts[] = 'Unit ESB: ' . $formula['finished_unit_raw'];
            }
            if ($formula['notes'] !== '') {
                $notesParts[] = $formula['notes'];
            }

            if (! $dryRun) {
                DB::transaction(function () use ($recipeCode, $formula, $outputItem, $matchedMaterials, $notesParts, &$stats) {
                    $existing = Recipe::where('recipe_code', $recipeCode)->first();

                    $recipe = Recipe::updateOrCreate(
                        ['recipe_code' => $recipeCode],
                        [
                            'name' => $formula['formula_name'],
                            'output_inventory_item_id' => $outputItem?->id,
                            'output_qty' => round((float) $formula['output_qty'], 2),
                            'output_unit' => $formula['output_unit'],
                            'division_id' => $outputItem?->division_id,
                            'notes' => implode(' | ', $notesParts),
                            'is_active' => true,
                        ]
                    );

                    if ($existing) {
                        $stats['updated']++;
                    } else {
                        $stats['created']++;
                    }

                    RecipeItem::where('recipe_id', $recipe->id)->delete();

                    foreach ($matchedMaterials as $matRow) {
                        RecipeItem::create([
                            'recipe_id' => $recipe->id,
                            'inventory_item_id' => $matRow['inventory_item_id'],
                            'qty' => round((float) $matRow['qty'], 4),
                            'unit' => $matRow['unit'],
                            'waste_percentage' => 0,
                        ]);
                    }
                });
            }

            if ($typeNorm === 'ASSEMBLY') {
                $stats['assembly_synced']++;
            } elseif ($typeNorm === 'MENU') {
                $stats['menu_synced']++;
            } else {
                $stats['other_synced']++;
            }
        }

        $this->newLine();
        $this->info($dryRun ? '=== RINGKASAN DRY-RUN IMPOR BOM ESB ===' : '=== RINGKASAN IMPOR BOM ESB ===');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total formula di file Excel', $stats['total_formulas']],
                ['Formula sesuai filter (' . implode(', ', $allowedTypes) . ')', $stats['filtered_formulas']],
                ['Formula Assembly (WIP) tersinkronisasi', $stats['assembly_synced']],
                ['Formula Menu tersinkronisasi', $stats['menu_synced']],
                ['Resep baru dibuat', $stats['created']],
                ['Resep diperbarui', $stats['updated']],
                ['Formula dilewati (0 bahan cocok di master)', $stats['skipped_no_materials']],
                ['Output WIP/Finished cocok dengan master', $stats['output_matched']],
                ['Baris komponen bahan cocok', $stats['material_lines_matched']],
                ['Baris komponen bahan belum cocok', $stats['material_lines_unmatched']],
            ]
        );

        if ($unmatchedMaterialNames !== []) {
            arsort($unmatchedMaterialNames);
            $topMissing = array_slice($unmatchedMaterialNames, 0, 10, true);
            $this->warn('Top 10 nama bahan di BOM ESB yang belum ada di master inventory_items:');
            foreach ($topMissing as $name => $count) {
                $this->line(" - {$name} ({$count}x)");
            }
        }

        return self::SUCCESS;
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
