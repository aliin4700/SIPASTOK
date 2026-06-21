<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'customer'");

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('module')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_outlet_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_monitor')->default(false);
            $table->boolean('can_cashier')->default(false);
            $table->boolean('can_manage_stock')->default(false);
            $table->boolean('can_manage_purchase')->default(false);
            $table->boolean('can_manage_finance')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'outlet_id']);
        });

        Schema::create('user_division_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_input_stock')->default(false);
            $table->boolean('can_input_waste')->default(false);
            $table->boolean('can_request_order')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'outlet_id', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_division_access');
        Schema::dropIfExists('user_outlet_access');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'admin', 'customer') NOT NULL DEFAULT 'customer'");
    }
};
