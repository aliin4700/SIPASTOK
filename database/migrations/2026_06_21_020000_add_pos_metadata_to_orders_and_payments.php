<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders MODIFY customer_id BIGINT UNSIGNED NULL');
        DB::statement("ALTER TABLE orders MODIFY transaction_method VARCHAR(50) NOT NULL DEFAULT 'pickup'");
        DB::statement("ALTER TABLE orders MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE payments MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'");

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'outlet_id')) {
                $table->foreignId('outlet_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'cashier_shift_id')) {
                $table->foreignId('cashier_shift_id')->nullable()->after('outlet_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'cashier_id')) {
                $table->foreignId('cashier_id')->nullable()->after('cashier_shift_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'order_channel')) {
                $table->string('order_channel')->default('online')->after('order_number');
            }

            if (! Schema::hasColumn('orders', 'service_type')) {
                $table->string('service_type')->nullable()->after('transaction_method');
            }

            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('service_type');
            }

            if (! Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone', 30)->nullable()->after('customer_name');
            }

            if (! Schema::hasColumn('orders', 'table_number')) {
                $table->string('table_number')->nullable()->after('customer_phone');
            }

            if (! Schema::hasColumn('orders', 'pickup_time')) {
                $table->dateTime('pickup_time')->nullable()->after('table_number');
            }

            if (! Schema::hasColumn('orders', 'order_source')) {
                $table->string('order_source')->nullable()->after('pickup_time');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cashier_shift_id')) {
                $table->foreignId('cashier_shift_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('payments', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('payments', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable()->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cashier_shift_id')) {
                $table->dropForeign(['cashier_shift_id']);
            }

            foreach (['cashier_shift_id', 'payment_channel', 'paid_amount', 'change_amount'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['outlet_id', 'cashier_shift_id', 'cashier_id'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropForeign([$column]);
                }
            }

            foreach ([
                'outlet_id',
                'cashier_shift_id',
                'cashier_id',
                'order_channel',
                'service_type',
                'customer_name',
                'customer_phone',
                'table_number',
                'pickup_time',
                'order_source',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
