<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('item_type');
            $table->string('base_unit');
            $table->string('purchase_unit')->nullable();
            $table->decimal('conversion_value', 15, 4)->nullable();
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->decimal('max_stock', 15, 2)->nullable();
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->string('default_supplier_name')->nullable();
            $table->decimal('last_purchase_price', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->decimal('reserved_stock', 15, 2)->default(0);
            $table->decimal('available_stock', 15, 2)->default(0);
            $table->string('unit')->nullable();
            $table->timestamp('last_stock_opname_at')->nullable();
            $table->timestamps();

            $table->unique(['outlet_id', 'inventory_item_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('qty', 15, 2);
            $table->string('unit')->nullable();
            $table->decimal('stock_before', 15, 2)->nullable();
            $table->decimal('stock_after', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('division_daily_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->date('operation_date');
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['outlet_id', 'division_id', 'operation_date'], 'division_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_daily_operations');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('inventory_items');
    }
};
