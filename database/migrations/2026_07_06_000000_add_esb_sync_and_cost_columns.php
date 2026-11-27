<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'esb_item_code')) {
                $table->string('esb_item_code')->nullable()->index()->after('code');
            }
            if (! Schema::hasColumn('inventory_items', 'esb_item_name')) {
                $table->string('esb_item_name')->nullable()->index()->after('esb_item_code');
            }
            if (! Schema::hasColumn('inventory_items', 'purchase_unit')) {
                $table->string('purchase_unit')->nullable()->after('base_unit');
            }
            if (! Schema::hasColumn('inventory_items', 'unit_conversion_factor')) {
                $table->decimal('unit_conversion_factor', 12, 4)->default(1)->after('purchase_unit');
            }
            if (! Schema::hasColumn('inventory_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 4)->default(0)->after('unit_conversion_factor');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            if (! Schema::hasColumn('outlets', 'esb_branch_name')) {
                $table->string('esb_branch_name')->nullable()->after('name');
            }
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_opname_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 4)->default(0)->after('difference_qty');
            } else {
                $table->decimal('unit_cost', 15, 4)->default(0)->change();
            }

            if (! Schema::hasColumn('stock_opname_items', 'variance_cost')) {
                $table->decimal('variance_cost', 15, 2)->default(0)->after('unit_cost');
            }
        });

        // Sinkronkan variance_cost dari variance_value yang sudah ada jika masih 0
        if (Schema::hasColumn('stock_opname_items', 'variance_value') && Schema::hasColumn('stock_opname_items', 'variance_cost')) {
            DB::table('stock_opname_items')
                ->where('variance_cost', 0)
                ->where('variance_value', '!=', 0)
                ->update(['variance_cost' => DB::raw('variance_value')]);
        }

        // Sinkronkan unit_conversion_factor dari conversion_value yang sudah ada bila > 0
        if (Schema::hasColumn('inventory_items', 'conversion_value') && Schema::hasColumn('inventory_items', 'unit_conversion_factor')) {
            DB::table('inventory_items')
                ->where('conversion_value', '>', 0)
                ->update(['unit_conversion_factor' => DB::raw('conversion_value')]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_opname_items', 'variance_cost')) {
                $table->dropColumn('variance_cost');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            if (Schema::hasColumn('outlets', 'esb_branch_name')) {
                $table->dropColumn('esb_branch_name');
            }
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $drop = [];
            foreach (['esb_item_code', 'esb_item_name', 'unit_conversion_factor', 'unit_cost'] as $col) {
                if (Schema::hasColumn('inventory_items', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
