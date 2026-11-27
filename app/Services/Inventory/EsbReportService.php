<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class EsbReportService
{
    /**
     * Normalisasi nama/kode item untuk pencocokan case-insensitive dan tahan spasi ganda.
     */
    public function normalizeKey(?string $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        // Hapus zero-width & non-breaking space lalu rapikan spasi ganda
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strtoupper(trim($text), 'UTF-8');
    }

    /**
     * Konversi string angka (format ESB / Excel desimal) menjadi float.
     */
    public function parseDecimal(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-') {
            return 0.0;
        }

        // Hapus spasi dan simbol mata uang
        $str = preg_replace('/[^\d.,\-]/', '', $str) ?? '';
        if ($str === '' || $str === '-') {
            return 0.0;
        }

        // Jika mengandung koma dan titik sekaligus, tentukan mana pemisah desimal
        if (str_contains($str, ',') && str_contains($str, '.')) {
            $lastComma = strrpos($str, ',');
            $lastDot = strrpos($str, '.');
            if ($lastComma > $lastDot) {
                // Format Indonesia: 1.234,56
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // Format Inggris: 1,234.56
                $str = str_replace(',', '', $str);
            }
        } elseif (str_contains($str, ',')) {
            // Jika hanya ada koma, cek apakah 3 digit di belakang (ribuan) atau desimal
            $parts = explode(',', $str);
            if (count($parts) === 2 && strlen($parts[1]) !== 3) {
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        }

        return is_numeric($str) ? (float) $str : 0.0;
    }

    /**
     * Baca sheet pertama dari file .xlsx menggunakan ZipArchive + SimpleXML.
     *
     * @return array<int, array<string, string>> Baris terindeks berdasarkan nomor baris Excel (1-based)
     */
    public function readFirstSheetRows(string $filePath): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("File Excel tidak ditemukan atau tidak dapat dibaca: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File bukan arsip XLSX yang valid.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx !== false) {
                foreach ($sx->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Cari sheet pertama (biasanya xl/worksheets/sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $name)) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Worksheet tidak ditemukan di dalam file XLSX.');
        }

        $sx = @simplexml_load_string($sheetXml);
        if ($sx === false || ! isset($sx->sheetData)) {
            throw new RuntimeException('Struktur XML worksheet pada file XLSX tidak valid.');
        }

        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $rowIndex = (int) ($row['r'] ?? (count($rows) + 1));
            $cells = [];

            foreach ($row->c as $c) {
                $ref = (string) ($c['r'] ?? '');
                preg_match('/^[A-Z]+/i', $ref, $matches);
                $col = strtoupper($matches[0] ?? '');
                if ($col === '') {
                    continue;
                }

                $type = (string) ($c['t'] ?? '');
                $value = isset($c->v) ? (string) $c->v : '';

                if ($type === 's') {
                    $idx = (int) $value;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    if (isset($c->is->t)) {
                        $value = (string) $c->is->t;
                    } elseif (isset($c->is->r)) {
                        $text = '';
                        foreach ($c->is->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $value = $text;
                    }
                }

                $cells[$col] = trim($value);
            }

            if ($cells !== []) {
                $rows[$rowIndex] = $cells;
            }
        }

        return $rows;
    }

    /**
     * Parse file Sales Material Usage Report dari ESB POS.
     *
     * @return array{meta: array<string, string>, header_row: int, rows: array<int, array<string, mixed>>}
     */
    public function parseSalesMaterialUsage(string $filePath): array
    {
        $sheetRows = $this->readFirstSheetRows($filePath);
        $headerRowNumber = null;
        $columnMap = [];
        $meta = [];

        foreach ($sheetRows as $rowNum => $cells) {
            $colA = $this->normalizeKey($cells['A'] ?? '');
            if ($headerRowNumber === null) {
                if (in_array($colA, ['PERIOD', 'GENERATED', 'BRANCH', 'REPORT FILE NAME'], true) && isset($cells['B'])) {
                    $meta[strtolower($colA)] = trim((string) $cells['B']);
                }

                $normalizedValues = array_map(fn ($v) => $this->normalizeKey((string) $v), $cells);
                if (in_array('BRANCH', $normalizedValues, true) && in_array('PRODUCT', $normalizedValues, true) && in_array('QTY', $normalizedValues, true)) {
                    $headerRowNumber = $rowNum;
                    foreach ($normalizedValues as $col => $headerName) {
                        $columnMap[$headerName] = $col;
                    }
                    break;
                }
            }
        }

        if ($headerRowNumber === null) {
            throw new RuntimeException('Header tabel Sales Material Usage ESB (Branch, Product, Qty) tidak ditemukan pada file.');
        }

        $branchCol = $columnMap['BRANCH'] ?? 'A';
        $productCol = $columnMap['PRODUCT'] ?? 'B';
        $codeCol = $columnMap['PRODUCT CODE'] ?? 'C';
        $categoryCol = $columnMap['CATEGORY'] ?? 'D';
        $unitCol = $columnMap['UNIT'] ?? 'E';
        $qtyCol = $columnMap['QTY'] ?? 'F';
        $amountCol = $columnMap['AMOUNT'] ?? 'G';

        $parsedRows = [];
        foreach ($sheetRows as $rowNum => $cells) {
            if ($rowNum <= $headerRowNumber) {
                continue;
            }

            $productName = trim((string) ($cells[$productCol] ?? ''));
            if ($productName === '' || $this->normalizeKey($productName) === 'TOTAL') {
                continue;
            }

            $qty = $this->parseDecimal($cells[$qtyCol] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $parsedRows[] = [
                'row_number' => $rowNum,
                'branch' => trim((string) ($cells[$branchCol] ?? '')),
                'product_name' => $productName,
                'product_code' => trim((string) ($cells[$codeCol] ?? '')),
                'category' => trim((string) ($cells[$categoryCol] ?? '')),
                'unit' => trim((string) ($cells[$unitCol] ?? '')),
                'qty' => round($qty, 4),
                'amount' => round($this->parseDecimal($cells[$amountCol] ?? 0), 2),
            ];
        }

        if ($parsedRows === []) {
            throw new RuntimeException('Tidak ada baris data pemakaian bahan dengan Qty > 0 pada file ESB tersebut.');
        }

        return [
            'meta' => $meta,
            'header_row' => $headerRowNumber,
            'rows' => $parsedRows,
        ];
    }

    /**
     * Cocokkan baris ESB Sales Material Usage dengan master InventoryItem dan stok outlet saat ini.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, matched_count: int, unmatched_count: int}
     */
    public function matchSalesRows(array $rows, int $outletId): array
    {
        $items = InventoryItem::where('is_active', true)->orderBy('name')->get();
        [$byName, $byCode] = $this->buildItemLookup($items);

        $stocksByItemId = InventoryStock::where('outlet_id', $outletId)
            ->pluck('current_stock', 'inventory_item_id')
            ->map(fn ($v) => (float) $v);

        $matchedCount = 0;
        $unmatchedCount = 0;
        $enriched = [];

        foreach ($rows as $index => $row) {
            $matchedItem = $this->findMatchingItem(
                $row['product_name'] ?? '',
                $row['product_code'] ?? '',
                $byName,
                $byCode
            );

            $currentStock = $matchedItem ? (float) ($stocksByItemId[$matchedItem->id] ?? 0.0) : null;
            $qty = (float) $row['qty'];
            $estimatedAfter = $currentStock !== null ? max(0.0, $currentStock - $qty) : null;

            if ($matchedItem) {
                $matchedCount++;
            } else {
                $unmatchedCount++;
            }

            $enriched[] = [
                ...$row,
                'index' => $index,
                'status' => $matchedItem ? 'matched' : 'unmatched',
                'matched_item_id' => $matchedItem?->id,
                'matched_item_name' => $matchedItem?->name,
                'matched_item_code' => $matchedItem?->code,
                'matched_item_unit' => $matchedItem?->base_unit,
                'matched_item_type' => $matchedItem?->item_type,
                'current_stock' => $currentStock,
                'estimated_after' => $estimatedAfter,
            ];
        }

        return [
            'rows' => $enriched,
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
        ];
    }

    /**
     * Parse file Bill Of Material Report dari ESB POS.
     *
     * @return array{meta: array<string, string>, header_row: int, formulas: array<int, array<string, mixed>>}
     */
    public function parseBillOfMaterial(string $filePath): array
    {
        $sheetRows = $this->readFirstSheetRows($filePath);
        $headerRowNumber = null;
        $columnMap = [];
        $meta = [];

        foreach ($sheetRows as $rowNum => $cells) {
            $colA = $this->normalizeKey($cells['A'] ?? '');
            if ($headerRowNumber === null) {
                if (in_array($colA, ['GENERATED', 'REPORT MODE', 'REPORT FILE NAME'], true) && isset($cells['B'])) {
                    $meta[strtolower($colA)] = trim((string) $cells['B']);
                }

                $normalizedValues = array_map(fn ($v) => $this->normalizeKey((string) $v), $cells);
                if (
                    in_array('FORMULA NAME', $normalizedValues, true)
                    && in_array('FORMULA TYPE', $normalizedValues, true)
                    && in_array('MATERIAL PRODUCT NAME', $normalizedValues, true)
                ) {
                    $headerRowNumber = $rowNum;
                    foreach ($normalizedValues as $col => $headerName) {
                        $columnMap[$headerName] = $col;
                    }
                    break;
                }
            }
        }

        if ($headerRowNumber === null) {
            throw new RuntimeException('Header tabel Bill Of Material ESB (Formula Name, Formula Type, Material Product Name) tidak ditemukan.');
        }

        $formulaNameCol = $columnMap['FORMULA NAME'] ?? 'A';
        $formulaCodeCol = $columnMap['FORMULA CODE'] ?? 'B';
        $formulaTypeCol = $columnMap['FORMULA TYPE'] ?? 'C';
        $appliedMenuCol = $columnMap['APPLIED TO MENU'] ?? 'D';
        $finishedNameCol = $columnMap['FINISHED PRODUCT NAME'] ?? 'E';
        $finishedCodeCol = $columnMap['FINISHED PRODUCT CODE'] ?? 'F';
        $finishedUnitCol = $columnMap['FINISHED UNIT'] ?? 'G';
        $bomCostCol = $columnMap['BOM COST TOTAL'] ?? 'H';
        $notesCol = $columnMap['NOTES'] ?? 'I';
        $matNameCol = $columnMap['MATERIAL PRODUCT NAME'] ?? 'J';
        $matCodeCol = $columnMap['MATERIAL PRODUCT CODE'] ?? 'K';
        $matUnitCol = $columnMap['MATERIAL UNIT'] ?? 'L';
        $matQtyCol = $columnMap['MATERIAL QTY'] ?? 'M';
        $matCostCol = $columnMap['MATERIAL BOM COST TOTAL'] ?? 'N';

        $grouped = [];

        foreach ($sheetRows as $rowNum => $cells) {
            if ($rowNum <= $headerRowNumber) {
                continue;
            }

            $formulaName = trim((string) ($cells[$formulaNameCol] ?? ''));
            $materialName = trim((string) ($cells[$matNameCol] ?? ''));
            if ($formulaName === '' || $materialName === '') {
                continue;
            }

            $formulaType = trim((string) ($cells[$formulaTypeCol] ?? ''));
            $formulaCode = trim((string) ($cells[$formulaCodeCol] ?? ''));
            $appliedMenu = trim((string) ($cells[$appliedMenuCol] ?? ''));
            $finishedName = trim((string) ($cells[$finishedNameCol] ?? ''));
            $finishedCode = trim((string) ($cells[$finishedCodeCol] ?? ''));
            $finishedUnitRaw = trim((string) ($cells[$finishedUnitCol] ?? ''));
            $bomCostTotal = round($this->parseDecimal($cells[$bomCostCol] ?? 0), 4);
            $notes = trim((string) ($cells[$notesCol] ?? ''));

            $materialCode = trim((string) ($cells[$matCodeCol] ?? ''));
            $materialUnit = trim((string) ($cells[$matUnitCol] ?? ''));
            $materialQty = $this->parseDecimal($cells[$matQtyCol] ?? 0);
            $materialCostTotal = round($this->parseDecimal($cells[$matCostCol] ?? 0), 4);

            if ($materialQty <= 0) {
                continue;
            }

            $groupKey = $this->normalizeKey($formulaType) . '|' . $this->normalizeKey($formulaName) . '|' . $this->normalizeKey($finishedName ?: $appliedMenu);

            if (! isset($grouped[$groupKey])) {
                [$outputQty, $outputUnit] = $this->parseFinishedUnit($finishedUnitRaw);
                $grouped[$groupKey] = [
                    'formula_name' => $formulaName,
                    'formula_code' => $formulaCode,
                    'formula_type' => $formulaType !== '' ? $formulaType : 'Unknown',
                    'applied_to_menu' => $appliedMenu,
                    'finished_product_name' => $finishedName !== '' ? $finishedName : ($appliedMenu !== '' ? $appliedMenu : $formulaName),
                    'finished_product_code' => $finishedCode,
                    'finished_unit_raw' => $finishedUnitRaw,
                    'output_qty' => $outputQty,
                    'output_unit' => $this->normalizeUnit($outputUnit),
                    'bom_cost_total' => $bomCostTotal,
                    'notes' => $notes,
                    'materials' => [],
                ];
            }

            $grouped[$groupKey]['materials'][] = [
                'name' => $materialName,
                'code' => $materialCode,
                'unit' => $this->normalizeUnit($materialUnit),
                'qty' => round($materialQty, 4),
                'bom_cost_total' => $materialCostTotal,
            ];
        }

        return [
            'meta' => $meta,
            'header_row' => $headerRowNumber,
            'formulas' => array_values($grouped),
        ];
    }

    /**
     * Ekstrak output_qty dan output_unit dari format ESB seperti "Batch@1500GR" atau "Batch@20ml".
     *
     * @return array{0: float, 1: string}
     */
    public function parseFinishedUnit(string $rawUnit): array
    {
        $rawUnit = trim($rawUnit);
        if ($rawUnit === '') {
            return [1.0, 'PORSI'];
        }

        if (preg_match('/@\s*([0-9]+(?:[.,][0-9]+)?)\s*([A-Za-z]+.*)$/u', $rawUnit, $m)) {
            $qty = $this->parseDecimal($m[1]);
            $unit = strtoupper(trim($m[2]));
            return [$qty > 0 ? $qty : 1.0, $unit !== '' ? $unit : 'UNIT'];
        }

        return [1.0, strtoupper($rawUnit)];
    }

    /**
     * Normalisasi satuan dasar ESB menjadi bentuk standar (GR, ML, PCS, PORSI, dll.).
     */
    public function normalizeUnit(?string $unit): string
    {
        $u = strtoupper(trim((string) $unit));
        if ($u === '') {
            return 'PCS';
        }

        return match ($u) {
            'GRAM', 'GR', 'G', 'GRM' => 'GR',
            'KG', 'KILO', 'KILOGRAM' => 'KG',
            'ML', 'MILILITER', 'CC' => 'ML',
            'L', 'LT', 'LTR', 'LITER' => 'L',
            'PC', 'PCS', 'PIECE', 'PIECES', 'BH', 'BUAH' => 'PCS',
            'PORSI', 'PORTION', 'PRS' => 'PORSI',
            'PACK', 'PK', 'PAK' => 'PACK',
            'BTL', 'BOTOL', 'BOTTLE' => 'BOTOL',
            'SLICE', 'SLC' => 'SLICE',
            default => $u,
        };
    }

    /**
     * Petakan kategori ESB ke item_type SIPASTOK.
     */
    public function mapEsbCategoryToItemType(?string $category, string $itemName = ''): string
    {
        $cat = $this->normalizeKey($category);
        $name = $this->normalizeKey($itemName);

        if (str_contains($cat, 'WIP') || str_contains($cat, 'SEMI') || str_starts_with($name, 'WIP ')) {
            return 'wip';
        }

        if (str_contains($cat, 'PACK') || str_contains($cat, 'KEMAS') || str_contains($cat, 'BOX') || str_contains($cat, 'CUP')) {
            return 'packaging';
        }

        if (str_contains($cat, 'FINISH') || str_contains($cat, 'MENU') || str_contains($cat, 'PRODUK JADI')) {
            return 'finished_goods';
        }

        return 'raw_material';
    }

    /**
     * @param  Collection<int, InventoryItem>  $items
     * @return array{0: array<string, InventoryItem>, 1: array<string, InventoryItem>}
     */
    public function buildItemLookup(Collection $items): array
    {
        $byName = [];
        $byCode = [];

        foreach ($items as $item) {
            $esbNameKey = $this->normalizeKey($item->esb_item_name ?? '');
            if ($esbNameKey !== '' && ! isset($byName[$esbNameKey])) {
                $byName[$esbNameKey] = $item;
            }

            $nameKey = $this->normalizeKey($item->name);
            if ($nameKey !== '' && ! isset($byName[$nameKey])) {
                $byName[$nameKey] = $item;
            }

            $esbCodeKey = $this->normalizeKey($item->esb_item_code ?? '');
            if ($esbCodeKey !== '' && ! isset($byCode[$esbCodeKey])) {
                $byCode[$esbCodeKey] = $item;
            }

            $codeKey = $this->normalizeKey($item->code);
            if ($codeKey !== '' && ! isset($byCode[$codeKey])) {
                $byCode[$codeKey] = $item;
            }
        }

        return [$byName, $byCode];
    }

    /**
     * @param  array<string, InventoryItem>  $byName
     * @param  array<string, InventoryItem>  $byCode
     */
    public function findMatchingItem(string $name, string $code, array $byName, array $byCode): ?InventoryItem
    {
        $nameKey = $this->normalizeKey($name);
        if ($nameKey !== '' && isset($byName[$nameKey])) {
            return $byName[$nameKey];
        }

        $codeKey = $this->normalizeKey($code);
        if ($codeKey !== '' && isset($byCode[$codeKey])) {
            return $byCode[$codeKey];
        }

        return null;
    }
}
