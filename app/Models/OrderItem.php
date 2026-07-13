<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot produk yang dibeli dalam satu order.
 */
class OrderItem extends Model
{
    // product_name dan price disimpan sebagai snapshot saat checkout/POS final.
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        // Item dimiliki oleh satu order.
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        // Relasi produk tetap ada untuk akses kategori/foto jika produk belum dihapus.
        return $this->belongsTo(Product::class);
    }

    public function getQtyAttribute(): int
    {
        // Alias lama `qty` dipertahankan untuk kompatibilitas view.
        return (int) $this->quantity;
    }

    public function setQtyAttribute(int $value): void
    {
        $this->attributes['quantity'] = $value;
    }

    public function getHargaAttribute(): string
    {
        return $this->price;
    }
}
