<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Profil customer dipisahkan dari users agar role internal tetap memakai tabel users.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('google_id')->nullable();
            $table->text('google_token')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->timestamps();
        });

        // Katalog produk frontend dan backend.
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('weight')->default(250);
            $table->string('main_photo')->nullable();
            $table->enum('status', ['draft', 'published', 'inactive'])->default('published');
            $table->timestamps();
        });

        Schema::create('product_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('photo');
            $table->timestamps();
        });

        // Cart adalah keranjang aktif customer, belum menjadi order.
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });

        // Order final dibuat dari checkout dan menyimpan ringkasan harga/alamat.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->enum('status', [
                'pending',
                'waiting_payment',
                'paid',
                'processing',
                'ready_for_pickup',
                'delivering',
                'shipped',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->enum('transaction_method', ['pickup', 'local_delivery', 'shipping'])->default('pickup');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'midtrans'])->default('cash');
            $table->enum('payment_status', [
                'pending',
                'waiting_confirmation',
                'paid',
                'failed',
                'expired',
                'cancelled',
                'rejected',
            ])->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->unsignedInteger('total_weight')->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->text('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // Payment dipisahkan agar verifikasi pembayaran tidak bercampur dengan status order.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'midtrans'])->default('cash');
            $table->enum('payment_status', [
                'pending',
                'waiting_confirmation',
                'paid',
                'failed',
                'expired',
                'cancelled',
                'rejected',
            ])->default('pending');
            $table->string('payment_proof')->nullable();
            $table->string('midtrans_order_id')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_snap_token')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Shipment menampung pickup, local delivery, dan shipping courier.
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('shipping_type', ['pickup', 'local_delivery', 'shipping'])->default('pickup');
            $table->string('courier')->nullable();
            $table->string('service')->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('estimated_delivery')->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('delivery_note')->nullable();
            $table->decimal('delivery_distance', 8, 2)->nullable();
            $table->timestamps();
        });

        // Chat customer-admin selalu terhubung ke order.
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sender_type', ['customer', 'admin']);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('product_photos');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('customers');
    }
};
