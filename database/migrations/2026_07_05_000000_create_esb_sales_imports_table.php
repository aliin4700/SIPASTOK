<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esb_sales_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->date('report_date');
            $table->string('report_period')->nullable();
            $table->string('file_name');
            $table->string('file_hash', 64);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['outlet_id', 'report_date', 'file_hash'], 'esb_sales_imports_unique_batch');
            $table->index(['outlet_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esb_sales_imports');
    }
};
