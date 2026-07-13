<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Address book customer untuk checkout shipping dan snapshot alamat order.
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('receiver_name');
            $table->string('receiver_phone', 30);
            $table->string('province_id')->nullable();
            $table->string('province_name')->nullable();
            $table->string('city_id')->nullable();
            $table->string('city_name')->nullable();
            // Komerce memakai district/subdistrict id sebagai tujuan cek ongkir.
            $table->string('district_id')->nullable();
            $table->string('district_name')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('full_address');
            $table->text('detail_address')->nullable();
            $table->string('label')->nullable();
            // Alamat default dipakai sebagai pilihan awal di checkout.
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
