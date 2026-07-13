<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Item produk di dalam cart customer.
 */
class CartItem extends Model
{
    use HasFactory;

    // Price disimpan di cart item sebagai snapshot saat produk dimasukkan.
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'qty',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function cart()
    {
        // Item selalu berada dalam satu cart.
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        // Produk referensi untuk nama, berat, dan stok.
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): float
    {
        // Subtotal memakai price snapshot, fallback ke harga produk bila kosong.
        $price = (float) ($this->price ?: $this->product->price);

        return (float) $this->quantity * $price;
    }

    public function getQtyAttribute(): int
    {
        // Alias lama `qty` tetap dibaca sebagai quantity.
        return (int) $this->quantity;
    }

    public function setQtyAttribute(int $value): void
    {
        $this->attributes['quantity'] = $value;
    }
}
