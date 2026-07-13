<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Keranjang belanja aktif customer sebelum checkout.
 */
class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
    ];

    public function customer()
    {
        // Cart dimiliki oleh satu customer.
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        // Isi cart berupa daftar produk dan quantity.
        return $this->hasMany(CartItem::class);
    }

    public function total(): float
    {
        // Total cart dihitung dari subtotal setiap item.
        return (float) $this->items->sum(fn (CartItem $item) => $item->subtotal());
    }
}
