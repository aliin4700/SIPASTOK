<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->boolean('is_blind_count')->default(true)->after('opname_type');
            $table->decimal('total_variance_value', 15, 2)->default(0)->after('status');
            $table->text('review_notes')->nullable()->after('notes');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('difference_qty');
            $table->decimal('variance_value', 15, 2)->default(0)->after('unit_cost');
            $table->string('variance_reason')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'variance_value', 'variance_reason']);
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropColumn(['is_blind_count', 'total_variance_value', 'review_notes']);
        });
    }
};
