<?php

namespace App\Console\Commands;

use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ImportMasterItemsCommand extends Command
{
    protected $signature = 'breadlab:import-master-items {--force-stock : Overwrite existing current_stock greater than 0} {--dry-run : Preview without saving}';

    protected $description = 'Import BreadLab temporary master item dataset from XLSX/CSV into inventory tables.';

    private array $summary = [
        'items_created' => 0,
        'items_updated' => 0,
        'stocks_created' => 0,
        'stocks_updated' => 0,
        'skipped_rows' => 0,
        'errors' => [],
    ];

    public function handle(): int
    {
        $base = storage_path('app/imports');
        $xlsx = $base . '/breadlab_master_item_sementara.xlsx';
        $itemsCsv = $base . '/inventory_items.csv';
        $stocksCsv = $base . '/inventory_stocks_seed.csv';

        if (is_file($xlsx)) {
            [$items, $stocks] = $this->readXlsx($xlsx);
        } elseif (is_file($itemsCsv) && is_file($stocksCsv)) {
            $items = $this->readCsv($itemsCsv);
            $stocks = $this->readCsv($stocksCsv);
        } else {
            $this->error('Import file not found. Expected XLSX or both CSV files in storage/app/imports.');
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->importItems($items);
            $this->importStocks($stocks);

            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        $this->printSummary();

        return empty($this->summary['errors']) ? self::SUCCESS : self::FAILURE;
    }

    private function importItems(array $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row = $this->normalizeKeys($row);
            $errors = $this->validateItem($row);

            if ($errors) {
                $this->skip($line, $errors);
                continue;
            }

            $division = $this->division($row['division'] ?? null);
            if (($row['division'] ?? '') !== '' && ! $division) {
                $this->skip($line, ['division not found: ' . $row['division']]);
                continue;
            }

            $payload = [
                'division_id' => $division?->id,
                'name' => trim($row['name']),
                'code' => $this->blankToNull($row['code'] ?? null),
                'item_type' => trim($row['item_type']),
                'base_unit' => trim($row['base_unit']),
                'purchase_unit' => $this->blankToNull($row['purchase_unit'] ?? null),
                'conversion_value' => $this->numberOrNull($row['conversion_value'] ?? null),
                'min_stock' => $this->number($row['min_stock'] ?? 0),
                'max_stock' => $this->numberOrNull($row['max_stock'] ?? null),
                'lead_time_days' => (int) $this->number($row['lead_time_days'] ?? 0),
                'default_supplier_name' => $this->blankToNull($row['default_supplier_name'] ?? null),
                'last_purchase_price' => $this->numberOrNull($row['last_purchase_price'] ?? null),
                'is_active' => $this->bool($row['is_active'] ?? true),
                'notes' => $this->blankToNull($row['notes'] ?? null),
            ];

            $lookup = $payload['code']
                ? ['code' => $payload['code']]
                : ['name' => $payload['name'], 'division_id' => $payload['division_id']];

            $existing = InventoryItem::where($lookup)->first();

            InventoryItem::updateOrCreate($lookup, $payload);

            $this->summary[$existing ? 'items_updated' : 'items_created']++;
        }
    }

    private function importStocks(array $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row = $this->normalizeKeys($row);
            $errors = $this->validateStock($row);

            if ($errors) {
                $this->skip($line, $errors);
                continue;
            }

            $outlet = Outlet::where('code', trim($row['outlet_code']))->first();
            $item = InventoryItem::where('code', trim($row['inventory_item_code']))->first();

            if (! $outlet) {
                $this->skip($line, ['outlet code not found: ' . $row['outlet_code']]);
                continue;
            }

            if (! $item) {
                $this->skip($line, ['inventory item code not found: ' . $row['inventory_item_code']]);
                continue;
            }

            $stock = InventoryStock::where('outlet_id', $outlet->id)
                ->where('inventory_item_id', $item->id)
                ->first();

            if ($stock && (float) $stock->current_stock > 0 && ! $this->option('force-stock')) {
                $this->skip($line, ['existing current_stock > 0, use --force-stock to overwrite']);
                continue;
            }

            $payload = [
                'current_stock' => $this->number($row['current_stock'] ?? 0),
                'reserved_stock' => $this->number($row['reserved_stock'] ?? 0),
                'available_stock' => $this->number($row['available_stock'] ?? (($row['current_stock'] ?? 0) - ($row['reserved_stock'] ?? 0))),
                'unit' => $this->blankToNull($row['unit'] ?? $item->base_unit),
            ];

            InventoryStock::updateOrCreate(
                ['outlet_id' => $outlet->id, 'inventory_item_id' => $item->id],
                $payload
            );

            $this->summary[$stock ? 'stocks_updated' : 'stocks_created']++;
        }
    }

    private function validateItem(array $row): array
    {
        $errors = [];

        if (($row['name'] ?? '') === '') {
            $errors[] = 'name required';
        }

        if (! in_array(($row['item_type'] ?? ''), InventoryItem::TYPES, true)) {
            $errors[] = 'invalid item_type';
        }

        if (($row['base_unit'] ?? '') === '') {
            $errors[] = 'base_unit required';
        }

        foreach (['min_stock', 'max_stock'] as $field) {
            if (($row[$field] ?? '') !== '' && ! is_numeric($row[$field])) {
                $errors[] = "{$field} must be numeric";
            }
        }

        return $errors;
    }

    private function validateStock(array $row): array
    {
        $errors = [];

        foreach (['outlet_code', 'inventory_item_code'] as $field) {
            if (($row[$field] ?? '') === '') {
                $errors[] = "{$field} required";
            }
        }

        foreach (['current_stock', 'reserved_stock', 'available_stock'] as $field) {
            if (($row[$field] ?? '') !== '' && ! is_numeric($row[$field])) {
                $errors[] = "{$field} must be numeric";
            }
        }

        return $errors;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open XLSX file.');
        }

        $sheets = $this->sheetMap($zip);
        $items = $this->rowsFromSheet($zip, $sheets['inventory_items'] ?? null);
        $stocks = $this->rowsFromSheet($zip, $sheets['inventory_stocks_seed'] ?? null);
        $zip->close();

        return [$items, $stocks];
    }

    private function sheetMap(ZipArchive $zip): array
    {
        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rels->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relationships = [];
        foreach ($rels->xpath('//r:Relationship') as $rel) {
            $target = ltrim((string) $rel['Target'], '/');
            $relationships[(string) $rel['Id']] = str_starts_with($target, 'xl/')
                ? $target
                : 'xl/' . $target;
        }

        $map = [];
        foreach ($workbook->xpath('//x:sheet') as $sheet) {
            $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $map[(string) $sheet['name']] = $relationships[(string) $attrs['id']] ?? null;
        }

        return $map;
    }

    private function rowsFromSheet(ZipArchive $zip, ?string $sheetPath): array
    {
        if (! $sheetPath || ! $zip->getFromName($sheetPath)) {
            return [];
        }

        $xml = simplexml_load_string($zip->getFromName($sheetPath));
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $header = [];
        $rows = [];

        foreach ($xml->xpath('//x:sheetData/x:row') as $row) {
            $values = [];
            foreach ($row->xpath('x:c') as $cell) {
                $column = preg_replace('/\d+/', '', (string) $cell['r']);
                $value = $cell->xpath('x:v');
                $values[$this->columnIndex($column)] = trim((string) ($value[0] ?? ''));
            }

            ksort($values);
            $values = array_values($values);

            if (! $header) {
                $header = array_map(fn ($value) => strtolower(trim($value)), $values);
                continue;
            }

            if (count(array_filter($values, fn ($value) => $value !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine(array_slice($header, 0, count($values)), $values);
        }

        return $rows;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = array_map(fn ($value) => strtolower(trim($value)), fgetcsv($handle) ?: []);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine(array_slice($header, 0, count($row)), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function division(?string $value): ?Division
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Division::where('code', $value)->orWhere('name', $value)->first();
    }

    private function skip(int $line, array $errors): void
    {
        $this->summary['skipped_rows']++;
        $this->summary['errors'][] = 'Row ' . $line . ': ' . implode(', ', $errors);
    }

    private function printSummary(): void
    {
        $this->info($this->option('dry-run') ? 'Dry-run summary:' : 'Import summary:');
        $this->line('Items created: ' . $this->summary['items_created']);
        $this->line('Items updated: ' . $this->summary['items_updated']);
        $this->line('Stocks created: ' . $this->summary['stocks_created']);
        $this->line('Stocks updated: ' . $this->summary['stocks_updated']);
        $this->line('Skipped rows: ' . $this->summary['skipped_rows']);

        foreach ($this->summary['errors'] as $error) {
            $this->warn($error);
        }
    }

    private function normalizeKeys(array $row): array
    {
        return collect($row)
            ->mapWithKeys(fn ($value, $key) => [strtolower(trim((string) $key)) => trim((string) $value)])
            ->all();
    }

    private function number(mixed $value): float
    {
        return (float) ($value === '' || $value === null ? 0 : $value);
    }

    private function numberOrNull(mixed $value): ?float
    {
        return $value === '' || $value === null ? null : (float) $value;
    }

    private function bool(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y', 'active', 'aktif'], true);
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split($column) as $char) {
            $index = $index * 26 + ord($char) - 64;
        }

        return $index - 1;
    }
}
