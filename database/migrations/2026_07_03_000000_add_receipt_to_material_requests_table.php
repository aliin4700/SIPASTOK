<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('material_requests', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('material_requests', 'receipt_original_name')) {
                $table->string('receipt_original_name')->nullable()->after('receipt_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            if (Schema::hasColumn('material_requests', 'receipt_original_name')) {
                $table->dropColumn('receipt_original_name');
            }

            if (Schema::hasColumn('material_requests', 'receipt_path')) {
                $table->dropColumn('receipt_path');
            }
        });
    }
};
