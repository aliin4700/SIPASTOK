<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('inventory_items', 'idx_inv_items_active_name', ['is_active', 'name']);
        $this->addIndexIfMissing('inventory_items', 'idx_inv_items_code', ['code']);
        $this->addIndexIfMissing('purchase_orders', 'idx_po_status_outlet_created', ['status', 'outlet_id', 'created_at']);
        $this->addIndexIfMissing('purchase_orders', 'idx_po_created_at', ['created_at']);
        $this->addIndexIfMissing('goods_receipts', 'idx_gr_status_outlet_created', ['status', 'outlet_id', 'created_at']);
        $this->addIndexIfMissing('goods_receipts', 'idx_gr_created_at', ['created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('inventory_items', 'idx_inv_items_active_name');
        $this->dropIndexIfExists('inventory_items', 'idx_inv_items_code');
        $this->dropIndexIfExists('purchase_orders', 'idx_po_status_outlet_created');
        $this->dropIndexIfExists('purchase_orders', 'idx_po_created_at');
        $this->dropIndexIfExists('goods_receipts', 'idx_gr_status_outlet_created');
        $this->dropIndexIfExists('goods_receipts', 'idx_gr_created_at');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::select(
                'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ? LIMIT 1',
                [$table, $indexName]
            );

            return $result !== [];
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

            return $result !== [];
        }

        return false;
    }
};
