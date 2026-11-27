<?php

namespace App\Console\Commands;

use App\Models\StockOpname;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateOpnameCostCommand extends Command
{
    protected $signature = 'sipastok:recalculate-opname-cost
        {--month= : Filter periode YYYY-MM tertentu (opsional, default semua periode)}
        {--force : Hitung ulang seluruh item meskipun unit_cost sudah > 0}';

    protected $description = 'Backfill unit_cost & variance_cost pada stock_opname_items historis dari inventory_items.unit_cost dan perbarui total_variance_value pada stock_opnames';

    public function handle(): int
    {
        $monthOption = $this->option('month');
        $force = (bool) $this->option('force');

        $query = DB::table('stock_opname_items as soi')
            ->join('stock_opnames as so', 'so.id', '=', 'soi.stock_opname_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'soi.inventory_item_id')
            ->where(function ($q) {
                $q->where('ii.unit_cost', '>', 0)
                    ->orWhere('ii.last_purchase_price', '>', 0);
            });

        if (! $force) {
            $query->where(function ($q) {
                $q->where('soi.unit_cost', '<=', 0)
                    ->orWhereNull('soi.unit_cost')
                    ->orWhere(function ($q2) {
                        $q2->where('soi.difference_qty', '!=', 0)
                            ->where('soi.variance_cost', '=', 0);
                    });
            });
        }

        if ($monthOption && preg_match('/^\d{4}-\d{2}$/', $monthOption)) {
            [$y, $m] = array_map('intval', explode('-', $monthOption));
            $query->whereYear('so.opname_date', $y)->whereMonth('so.opname_date', $m);
        }

        $rows = $query->select([
            'soi.id',
            'soi.stock_opname_id',
            'soi.system_stock',
            'soi.physical_stock',
            'soi.difference_qty',
            'soi.unit_cost as current_unit_cost',
            'ii.unit_cost as master_unit_cost',
            'ii.last_purchase_price as master_last_price',
        ])->get();

        $updatedItems = 0;
        $affectedOpnameIds = [];

        DB::transaction(function () use ($rows, $force, &$updatedItems, &$affectedOpnameIds) {
            foreach ($rows as $row) {
                $unitCost = (! $force && (float) $row->current_unit_cost > 0)
                    ? (float) $row->current_unit_cost
                    : ((float) $row->master_unit_cost > 0
                        ? (float) $row->master_unit_cost
                        : (float) $row->master_last_price);

                if ($unitCost <= 0) {
                    continue;
                }

                $diffQty = abs((float) $row->difference_qty) > 0.00001
                    ? (float) $row->difference_qty
                    : ((float) $row->physical_stock - (float) $row->system_stock);

                $varianceCost = round($diffQty * $unitCost, 2);

                DB::table('stock_opname_items')
                    ->where('id', $row->id)
                    ->update([
                        'difference_qty' => round($diffQty, 2),
                        'unit_cost' => round($unitCost, 4),
                        'variance_cost' => $varianceCost,
                        'variance_value' => $varianceCost,
                        'updated_at' => now(),
                    ]);

                $updatedItems++;
                $affectedOpnameIds[$row->stock_opname_id] = true;
            }

            // Perbarui total_variance_value pada seluruh header stock_opnames yang terdampak
            foreach (array_keys($affectedOpnameIds) as $opnameId) {
                $sumVariance = (float) DB::table('stock_opname_items')
                    ->where('stock_opname_id', $opnameId)
                    ->sum('variance_cost');

                DB::table('stock_opnames')
                    ->where('id', $opnameId)
                    ->update([
                        'total_variance_value' => round($sumVariance, 2),
                        'updated_at' => now(),
                    ]);
            }
        });

        // Ringkasan periode 2026-11 untuk verifikasi
        $novSummary = DB::table('stock_opname_items as soi')
            ->join('stock_opnames as so', 'so.id', '=', 'soi.stock_opname_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'soi.inventory_item_id')
            ->whereYear('so.opname_date', 2026)
            ->whereMonth('so.opname_date', 11)
            ->whereIn('so.status', ['reviewed', 'approved'])
            ->selectRaw('COUNT(DISTINCT so.id) as sessions, COUNT(soi.id) as items, COALESCE(SUM(COALESCE(NULLIF(soi.variance_cost, 0), soi.difference_qty * ii.unit_cost)), 0) as total_variance')
            ->first();

        $this->info('=== RINGKASAN RECALCULATE OPNAME COST ===');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Baris stock_opname_items diperbarui', number_format($updatedItems)],
                ['Header stock_opnames diperbarui', number_format(count($affectedOpnameIds))],
                ['Sesi reviewed/approved periode 2026-11', number_format((int) ($novSummary->sessions ?? 0))],
                ['Total item opname periode 2026-11', number_format((int) ($novSummary->items ?? 0))],
                ['Total Selisih Nilai periode 2026-11 (Rp)', 'Rp ' . number_format((float) ($novSummary->total_variance ?? 0), 2, ',', '.')],
            ]
        );

        return self::SUCCESS;
    }
}
