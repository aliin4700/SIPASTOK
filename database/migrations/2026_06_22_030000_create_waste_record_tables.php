<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_records', function (Blueprint $table) {
            $table->id();
            $table->string('waste_number')->nullable()->unique();
            $table->foreignId('division_daily_operation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('waste_date');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('waste_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name')->nullable();
            $table->string('item_type')->nullable();
            $table->decimal('qty', 15, 2);
            $table->string('unit')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_record_items');
        Schema::dropIfExists('waste_records');
    }
};
