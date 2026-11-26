<?php

namespace App\Console\Commands;

use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

class ImportMedjaCeritaOpname extends Command
{
    protected $signature = 'sipastok:import-medja-cerita {file} {--outlet=} {--division=} {--with-daily-history} {--fresh}';

    protected $description = 'Impor dataset Stock Opname Medja Cerita (.xlsx multi-sheet maupun .csv) ke database SIPASTOK';

    private const SHEET_MAP = [
        'KITCHEN (MEJASEM)' => [
            'outlet' => ['code' => 'MJS', 'name' => 'Mejasem'],
            'division' => ['code' => 'KIT', 'name' => 'Kitchen'],
        ],
        'BAR (MEJASEM)' => [
            'outlet' => ['code' => 'MJS', 'name' => 'Mejasem'],
            'division' => ['code' => 'BAR', 'name' => 'Bar'],
        ],
        'CORNER (MEJASEM)' => [
            'outlet' => ['code' => 'MJS', 'name' => 'Mejasem'],
            'division' => ['code' => 'CRN', 'name' => 'Corner'],
        ],
        'FLOOR (MEJASEM)' => [
            'outlet' => ['code' => 'MJS', 'name' => 'Mejasem'],
            'division' => ['code' => 'FLR', 'name' => 'Floor'],
        ],
        'PASTRY (MEJASEM)' => [
            'outlet' => ['code' => 'MJS', 'name' => 'Mejasem'],
            'division' => ['code' => 'PST', 'name' => 'Pastry'],
        ],
        'SLAWI (KITCHEN)' => [
            'outlet' => ['code' => 'SLW', 'name' => 'Slawi'],
            'division' => ['code' => 'KIT', 'name' => 'Kitchen'],
        ],
        'SLAWI (BAR)' => [
            'outlet' => ['code' => 'SLW', 'name' => 'Slawi'],
            'division' => ['code' => 'BAR', 'name' => 'Bar'],
        ],
        'SLAWI (PACKAGING)' => [
            'outlet' => ['code' => 'SLW', 'name' => 'Slawi'],
            'division' => ['code' => 'PKG', 'name' => 'Packaging'],
        ],
        'KAGOK CK' => [
            'outlet' => ['code' => 'KGK', 'name' => 'Kagok CK'],
            'division' => ['code' => 'CK', 'name' => 'Central Kitchen'],
        ],
    ];

    private const MONTH_MAP = [
        'januari' => 'January',
        'februari' => 'February',
        'maret' => 'March',
        'april' => 'April',
        'mei' => 'May',
        'juni' => 'June',
        'juli' => 'July',
        'agustus' => 'August',
        'september' => 'September',
        'oktober' => 'October',
        'november' => 'November',
        'desember' => 'December',
    ];

    public function handle(): int
    {
        $fileArg = (string) $this->argument('file');
        $filePath = file_exists($fileArg) ? $fileArg : base_path($fileArg);

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$fileArg}");
            return self::FAILURE;
        }

        $withDailyHistory = (bool) $this->option('with-daily-history');
        $fresh = (bool) $this->option('fresh');
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            $sheets = $ext === 'xlsx'
                ? $this->readXlsxSheets($filePath)
                : ['CSV_IMPORT' => $this->readCsvRows($filePath)];
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($fresh) {
            DB::transaction(function () {
                StockOpnameItem::query()->delete();
                StockOpname::query()->delete();
            });
            $this->info('Data stock_opnames lama telah dibersihkan (--fresh).');
        }

        $divisionSeq = [];
        $stats = [
            'sheets_processed' => 0,
            'sheets_skipped' => 0,
            'outlets' => [],
            'divisions' => [],
            'items_touched' => [],
            'stocks_updated' => 0,
            'opname_sessions' => 0,
        ];

        $user = User::first();

        foreach ($sheets as $sheetName => $rows) {
            $normalizedSheetName = trim(strtoupper($sheetName));

            if (! $this->isValidOpnameSheet($rows)) {
                $this->line("Melewati sheet non-opname / pembatas: {$sheetName}");
                $stats['sheets_skipped']++;
                continue;
            }

            [$outletMeta, $divisionMeta] = $this->resolveOutletAndDivision($normalizedSheetName, $filePath);
            $dateHeaders = $this->parseDateColumns($rows[6] ?? []);

            $this->info(sprintf(
                'Memproses sheet [%s] -> Outlet: %s (%s), Divisi: %s (%s), Kolom Tanggal: %d',
                $sheetName,
                $outletMeta['name'],
                $outletMeta['code'],
                $divisionMeta['name'],
                $divisionMeta['code'],
                count($dateHeaders)
            ));

            DB::transaction(function () use (
                $rows,
                $outletMeta,
                $divisionMeta,
                $dateHeaders,
                $withDailyHistory,
                $user,
                &$divisionSeq,
                &$stats
            ) {
                $outlet = Outlet::firstOrCreate(
                    ['code' => $outletMeta['code']],
                    [
                        'name' => $outletMeta['name'],
                        'address' => $outletMeta['name'] . ', Tegal',
                        'is_active' => true,
                    ]
                );
                if ($outlet->name !== $outletMeta['name'] || ! $outlet->is_active) {
                    $outlet->update(['name' => $outletMeta['name'], 'is_active' => true]);
                }

                $division = Division::firstOrCreate(
                    ['code' => $divisionMeta['code']],
                    [
                        'name' => $divisionMeta['name'],
                        'description' => 'Divisi ' . $divisionMeta['name'] . ' Medja Cerita',
                        'is_active' => true,
                    ]
                );
                if ($division->name !== $divisionMeta['name'] || ! $division->is_active) {
                    $division->update(['name' => $divisionMeta['name'], 'is_active' => true]);
                }

                $stats['outlets'][$outlet->code] = $outlet->name;
                $stats['divisions'][$division->code] = $division->name;

                if (! isset($divisionSeq[$division->code])) {
                    $divisionSeq[$division->code] = 1;
                }

                $dailyBuckets = [];

                for ($i = 8, $totalRows = count($rows); $i < $totalRows; $i++) {
                    $row = $rows[$i] ?? [];
                    $rawName = trim((string) ($row[1] ?? ''));

                    if ($rawName === '') {
                        continue;
                    }

                    $rawUnit = trim(strtolower((string) ($row[2] ?? '')));
                    $baseUnit = $rawUnit !== '' ? $rawUnit : 'pcs';
                    $minStock = $this->parseNumber($row[3] ?? 0, 0.0);
                    $lastMonthStock = $this->parseNumber($row[4] ?? 0, 0.0);

                    $itemType = $this->resolveItemType($rawName, $division->name);

                    $item = InventoryItem::where('division_id', $division->id)
                        ->where('name', $rawName)
                        ->first();

                    if (! $item) {
                        do {
                            $code = sprintf('%s-%03d', $division->code, $divisionSeq[$division->code]++);
                        } while (InventoryItem::where('code', $code)->exists());

                        $item = InventoryItem::create([
                            'division_id' => $division->id,
                            'name' => $rawName,
                            'code' => $code,
                            'item_type' => $itemType,
                            'base_unit' => $baseUnit,
                            'purchase_unit' => $baseUnit,
                            'conversion_value' => 1,
                            'min_stock' => $minStock,
                            'lead_time_days' => 0,
                            'last_purchase_price' => 0,
                            'is_active' => true,
                        ]);
                    } else {
                        $code = $item->code;
                        if (! $code) {
                            do {
                                $code = sprintf('%s-%03d', $division->code, $divisionSeq[$division->code]++);
                            } while (InventoryItem::where('code', $code)->where('id', '!=', $item->id)->exists());
                        }

                        $item->update([
                            'code' => $code,
                            'item_type' => $itemType,
                            'base_unit' => $baseUnit,
                            'min_stock' => max((float) $item->min_stock, $minStock),
                            'is_active' => true,
                        ]);
                    }

                    $stats['items_touched'][$item->id] = true;

                    $latestFinalStock = $lastMonthStock;
                    $latestOpnameDate = null;
                    $runningStock = $lastMonthStock;

                    foreach ($dateHeaders as $colIndex => $dateObj) {
                        $inCell = $row[$colIndex] ?? null;
                        $finalStockCell = $row[$colIndex + 1] ?? null;
                        $wasteCell = $row[$colIndex + 2] ?? null;
                        $usedCell = $row[$colIndex + 3] ?? null;

                        $inQty = $this->parseNumber($inCell, 0.0);
                        $wasteQty = $this->parseNumber($wasteCell, 0.0);
                        $usedQty = $this->parseNumber($usedCell, 0.0);

                        if ($this->hasNumber($finalStockCell)) {
                            $finalStock = max(0.0, $this->parseNumber($finalStockCell, 0.0));
                            $latestFinalStock = $finalStock;
                            $latestOpnameDate = $dateObj;

                            if ($withDailyHistory) {
                                $dateKey = $dateObj->format('Y-m-d');
                                $expectedSystemStock = $usedQty < 0
                                    ? max(0.0, $runningStock + $inQty - $wasteQty)
                                    : $finalStock;
                                $diffQty = $finalStock - $expectedSystemStock;
                                $unitCost = (float) ($item->last_purchase_price ?? 0);

                                $dailyBuckets[$dateKey]['date'] = $dateObj;
                                $dailyBuckets[$dateKey]['items'][$item->id] = [
                                    'inventory_item_id' => $item->id,
                                    'system_stock' => $expectedSystemStock,
                                    'physical_stock' => $finalStock,
                                    'difference_qty' => $diffQty,
                                    'unit_cost' => $unitCost,
                                    'variance_value' => $diffQty * $unitCost,
                                    'unit' => $baseUnit,
                                    'variance_reason' => abs($diffQty) > 0.0001 ? 'admin_error' : null,
                                    'notes' => $wasteQty > 0 ? "Waste tercatat: {$wasteQty} {$baseUnit}" : null,
                                ];
                            }

                            $runningStock = $finalStock;
                        }
                    }

                    $stock = InventoryStock::firstOrNew([
                        'outlet_id' => $outlet->id,
                        'inventory_item_id' => $item->id,
                    ]);

                    $reserved = (float) ($stock->reserved_stock ?? 0);
                    $stock->fill([
                        'current_stock' => $latestFinalStock,
                        'reserved_stock' => $reserved,
                        'available_stock' => $latestFinalStock - $reserved,
                        'unit' => $baseUnit,
                        'last_stock_opname_at' => $latestOpnameDate ? $latestOpnameDate->copy()->endOfDay() : now(),
                    ])->save();

                    $stats['stocks_updated']++;
                }

                if ($withDailyHistory && ! empty($dailyBuckets)) {
                    foreach ($dailyBuckets as $dateKey => $bucket) {
                        /** @var Carbon $dateObj */
                        $dateObj = $bucket['date'];
                        $itemsPayload = array_values($bucket['items'] ?? []);

                        if (empty($itemsPayload)) {
                            continue;
                        }

                        $opnameNumber = sprintf('SO-%s-%s-%s', $outlet->code, $division->code, $dateObj->format('Ymd'));
                        $totalVarianceValue = array_sum(array_column($itemsPayload, 'variance_value'));

                        $opname = StockOpname::updateOrCreate(
                            ['opname_number' => $opnameNumber],
                            [
                                'outlet_id' => $outlet->id,
                                'division_id' => $division->id,
                                'counted_by' => $user?->id,
                                'opname_date' => $dateKey,
                                'opname_type' => 'daily_closing',
                                'is_blind_count' => true,
                                'status' => 'reviewed',
                                'total_variance_value' => $totalVarianceValue,
                                'notes' => sprintf(
                                    'Impor riwayat stok opname harian Medja Cerita - %s / %s (%s)',
                                    $outlet->name,
                                    $division->name,
                                    $dateObj->format('d/m/Y')
                                ),
                                'review_notes' => 'Diverifikasi otomatis dari dataset Oktober Medja Cerita.',
                                'submitted_at' => $dateObj->copy()->setTime(22, 0),
                                'reviewed_by' => $user?->id,
                                'reviewed_at' => $dateObj->copy()->setTime(22, 30),
                            ]
                        );

                        $opname->items()->delete();
                        $timestamp = now();
                        $insertRows = array_map(fn (array $row) => [
                            'stock_opname_id' => $opname->id,
                            ...$row,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ], $itemsPayload);

                        foreach (array_chunk($insertRows, 100) as $chunk) {
                            StockOpnameItem::insert($chunk);
                        }

                        $stats['opname_sessions']++;
                    }
                }
            });

            $stats['sheets_processed']++;
        }

        $this->newLine();
        $this->info('=== RINGKASAN IMPOR DATASET MEDJA CERITA ===');
        $this->line('Sheet Diproses      : ' . $stats['sheets_processed'] . ' (Dilewati: ' . $stats['sheets_skipped'] . ')');
        $this->line('Outlet Terdaftar    : ' . count($stats['outlets']) . ' (' . implode(', ', $stats['outlets']) . ')');
        $this->line('Divisi Terdaftar    : ' . count($stats['divisions']) . ' (' . implode(', ', $stats['divisions']) . ')');
        $this->line('Total InventoryItem : ' . InventoryItem::count() . ' item (Diimpor/Diperbarui: ' . count($stats['items_touched']) . ')');
        $this->line('Total InventoryStock: ' . InventoryStock::count() . ' stok outlet (Diperbarui: ' . $stats['stocks_updated'] . ')');
        $this->line('Total StockOpname   : ' . StockOpname::count() . ' sesi (Diimpor/Diperbarui: ' . $stats['opname_sessions'] . ')');

        return self::SUCCESS;
    }

    private function isValidOpnameSheet(array $rows): bool
    {
        if (count($rows) < 9) {
            return false;
        }

        $headerRow = $rows[6] ?? [];
        $unitHeader = strtoupper(trim((string) ($headerRow[2] ?? '')));
        $lastMonthHeader = strtoupper(trim((string) ($headerRow[4] ?? '')));

        return $unitHeader === 'UNIT' || str_contains($lastMonthHeader, 'LAST MONTH STOCK');
    }

    /**
     * @return array{0: array{code: string, name: string}, 1: array{code: string, name: string}}
     */
    private function resolveOutletAndDivision(string $normalizedSheetName, string $filePath): array
    {
        $outletOpt = trim((string) $this->option('outlet'));
        $divisionOpt = trim((string) $this->option('division'));

        if (isset(self::SHEET_MAP[$normalizedSheetName])) {
            $mapped = self::SHEET_MAP[$normalizedSheetName];
            if ($outletOpt !== '') {
                $mapped['outlet']['name'] = $outletOpt;
            }
            if ($divisionOpt !== '') {
                $mapped['division']['name'] = $divisionOpt;
            }
            return [$mapped['outlet'], $mapped['division']];
        }

        foreach (self::SHEET_MAP as $sheetKey => $mapped) {
            if (str_contains(strtoupper($filePath), $sheetKey)) {
                if ($outletOpt !== '') {
                    $mapped['outlet']['name'] = $outletOpt;
                }
                if ($divisionOpt !== '') {
                    $mapped['division']['name'] = $divisionOpt;
                }
                return [$mapped['outlet'], $mapped['division']];
            }
        }

        $outletName = $outletOpt !== '' ? $outletOpt : 'Mejasem';
        $divisionName = $divisionOpt !== '' ? $divisionOpt : 'Kitchen';

        $outletCode = match (strtoupper($outletName)) {
            'SLAWI' => 'SLW',
            'KAGOK CK', 'KAGOK' => 'KGK',
            default => 'MJS',
        };

        $divisionCode = match (strtoupper($divisionName)) {
            'BAR' => 'BAR',
            'CORNER' => 'CRN',
            'FLOOR' => 'FLR',
            'PASTRY' => 'PST',
            'PACKAGING' => 'PKG',
            'CENTRAL KITCHEN', 'CK' => 'CK',
            default => 'KIT',
        };

        return [
            ['code' => $outletCode, 'name' => $outletName],
            ['code' => $divisionCode, 'name' => $divisionName],
        ];
    }

    private function resolveItemType(string $itemName, string $divisionName): string
    {
        if (preg_match('/^WIP[\.\s]/i', $itemName) === 1) {
            return 'wip';
        }

        if (in_array(strtolower(trim($divisionName)), ['packaging', 'floor'], true)) {
            return 'packaging';
        }

        return 'raw_material';
    }

    /**
     * @return array<int, Carbon>
     */
    private function parseDateColumns(array $headerRow): array
    {
        $dates = [];

        // Edge-case PASTRY (MEJASEM): kolom index 5 kosong (' '), sementara kolom index 9 adalah '2 oktober 2026'
        $col5 = trim((string) ($headerRow[5] ?? ''));
        $col9 = trim(strtolower((string) ($headerRow[9] ?? '')));
        if ($col5 === '' && str_starts_with($col9, '2 oktober 2026')) {
            $dates[5] = Carbon::parse('2026-10-01')->startOfDay();
        }

        foreach ($headerRow as $index => $cell) {
            if ($index < 5 || isset($dates[$index])) {
                continue;
            }

            $text = trim(strtolower((string) $cell));
            if ($text === '') {
                continue;
            }

            $normalized = str_ireplace(
                array_keys(self::MONTH_MAP),
                array_values(self::MONTH_MAP),
                $text
            );

            try {
                $dates[$index] = Carbon::parse($normalized)->startOfDay();
            } catch (\Throwable) {
                // Abaikan kolom yang bukan tanggal valid
            }
        }

        ksort($dates);

        return $dates;
    }

    /**
     * @return array<string, array<int, array<int, string>>>
     */
    private function readXlsxSheets(string $filePath): array
    {
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $result = [];
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $result[$worksheet->getTitle()] = $worksheet->toArray(null, true, false, false);
            }
            return $result;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Tidak dapat membuka arsip XLSX: {$filePath}");
        }

        $sharedStrings = [];
        $sharedXmlStr = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlStr !== false) {
            $sharedXml = simplexml_load_string($sharedXmlStr);
            if ($sharedXml !== false && isset($sharedXml->si)) {
                foreach ($sharedXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) ($run->t ?? '');
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $relsMap = [];
        $relsXmlStr = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXmlStr !== false) {
            $relsXml = simplexml_load_string($relsXmlStr);
            if ($relsXml !== false) {
                foreach ($relsXml->Relationship as $rel) {
                    $id = (string) $rel['Id'];
                    $target = ltrim((string) $rel['Target'], '/');
                    if (! str_starts_with($target, 'xl/')) {
                        $target = 'xl/' . $target;
                    }
                    $relsMap[$id] = $target;
                }
            }
        }

        $workbookXmlStr = $zip->getFromName('xl/workbook.xml');
        if ($workbookXmlStr === false) {
            $zip->close();
            throw new RuntimeException('File xl/workbook.xml tidak ditemukan di dalam XLSX.');
        }

        $workbookXml = simplexml_load_string($workbookXmlStr);
        $sheetsData = [];

        if ($workbookXml !== false && isset($workbookXml->sheets->sheet)) {
            foreach ($workbookXml->sheets->sheet as $sheetNode) {
                $sheetName = (string) $sheetNode['name'];
                $rAttrs = $sheetNode->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $rId = (string) ($rAttrs['id'] ?? '');
                $targetPath = $relsMap[$rId] ?? null;

                if (! $targetPath) {
                    continue;
                }

                $sheetXmlStr = $zip->getFromName($targetPath);
                if ($sheetXmlStr === false) {
                    continue;
                }

                $sheetsData[$sheetName] = $this->parseWorksheetXml($sheetXmlStr, $sharedStrings);
            }
        }

        $zip->close();

        return $sheetsData;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function parseWorksheetXml(string $sheetXmlStr, array $sharedStrings): array
    {
        $xml = simplexml_load_string($sheetXmlStr);
        if ($xml === false || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        $maxRowIdx = 0;

        foreach ($xml->sheetData->row as $rowNode) {
            $rowNumber = (int) ($rowNode['r'] ?? 0);
            $rowIdx = $rowNumber > 0 ? $rowNumber - 1 : count($rows);
            $maxRowIdx = max($maxRowIdx, $rowIdx);

            $rowCells = [];
            $maxColIdx = -1;

            foreach ($rowNode->c as $cellNode) {
                $cellRef = (string) ($cellNode['r'] ?? '');
                $colIdx = $cellRef !== '' ? $this->columnIndexFromCellRef($cellRef) : ($maxColIdx + 1);
                $maxColIdx = max($maxColIdx, $colIdx);

                $cellType = (string) ($cellNode['t'] ?? '');
                $value = '';

                if ($cellType === 's') {
                    $ssIdx = (int) ($cellNode->v ?? -1);
                    $value = $sharedStrings[$ssIdx] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    $value = (string) ($cellNode->is->t ?? '');
                } else {
                    $value = isset($cellNode->v) ? (string) $cellNode->v : '';
                }

                $rowCells[$colIdx] = $value;
            }

            if ($maxColIdx >= 0) {
                $normalizedRow = [];
                for ($c = 0; $c <= $maxColIdx; $c++) {
                    $normalizedRow[$c] = $rowCells[$c] ?? '';
                }
                $rows[$rowIdx] = $normalizedRow;
            } else {
                $rows[$rowIdx] = [];
            }
        }

        $finalRows = [];
        for ($r = 0; $r <= $maxRowIdx; $r++) {
            $finalRows[$r] = $rows[$r] ?? [];
        }

        return $finalRows;
    }

    private function columnIndexFromCellRef(string $cellRef): int
    {
        if (! preg_match('/^([A-Z]+)/i', $cellRef, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        $len = strlen($letters);

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsvRows(string $filePath): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $rows[] = array_map(fn ($v) => (string) ($v ?? ''), $data);
            }
            fclose($handle);
        }
        return $rows;
    }

    private function hasNumber(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $cleaned = str_replace([',', ' '], '', trim((string) $value));
        return $cleaned !== '' && is_numeric($cleaned);
    }

    private function parseNumber(mixed $value, float $default = 0.0): float
    {
        if (! $this->hasNumber($value)) {
            return $default;
        }

        return (float) str_replace([',', ' '], '', trim((string) $value));
    }
}
