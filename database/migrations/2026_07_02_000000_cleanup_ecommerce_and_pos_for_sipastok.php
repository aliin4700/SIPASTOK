<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recipes') && Schema::hasColumn('recipes', 'product_id')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_id');
            });
        }

        if (Schema::hasTable('production_record_items') && Schema::hasColumn('production_record_items', 'product_id')) {
            Schema::table('production_record_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_id');
            });
        }

        if (Schema::hasTable('user_outlet_access')) {
            Schema::table('user_outlet_access', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('user_outlet_access', 'can_cashier')) {
                    $dropCols[] = 'can_cashier';
                }
                if (Schema::hasColumn('user_outlet_access', 'can_manage_finance')) {
                    $dropCols[] = 'can_manage_finance';
                }
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->whereIn('role', ['customer', 'cashier'])->delete();
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'staff_outlet'");
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->whereIn('name', ['customer', 'cashier'])->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('module', ['cashier', 'finance'])->delete();
        }

        $tablesToDrop = [
            'messages',
            'conversations',
            'shipments',
            'payments',
            'order_items',
            'orders',
            'cashier_cash_movements',
            'cashier_shifts',
            'cart_items',
            'carts',
            'product_photos',
            'products',
            'categories',
            'customer_addresses',
            'customers',
            'operational_expenses',
            'sales_targets',
        ];

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('migrations')) {
            DB::table('migrations')->whereIn('migration', [
                '2026_06_19_000000_create_breadlab_foundation_tables',
                '2026_06_19_010000_create_customer_addresses_table',
                '2026_06_20_010000_add_coordinates_to_customer_addresses_table',
                '2026_06_21_010000_create_cashier_foundation_tables',
                '2026_06_21_020000_add_pos_metadata_to_orders_and_payments',
                '2026_06_22_000000_create_finance_foundation_tables',
            ])->delete();
        }
    }

    public function down(): void
    {
        // Migrasi pembersihan E-Commerce & POS bersifat permanen untuk SIPASTOK.
    }
};
