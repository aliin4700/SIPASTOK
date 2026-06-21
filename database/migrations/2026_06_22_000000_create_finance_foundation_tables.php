<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period_type');
            $table->date('target_date')->nullable();
            $table->unsignedTinyInteger('target_month')->nullable();
            $table->unsignedSmallInteger('target_year')->nullable();
            $table->decimal('target_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_type', 'target_date']);
            $table->index(['target_month', 'target_year']);
        });

        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->unsignedTinyInteger('expense_month')->nullable();
            $table->unsignedSmallInteger('expense_year')->nullable();
            $table->string('category');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('recorded');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['expense_date', 'status']);
            $table->index(['expense_month', 'expense_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_expenses');
        Schema::dropIfExists('sales_targets');
    }
};
