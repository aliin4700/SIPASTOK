<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

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
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): float
    {
        $price = (float) ($this->price ?: $this->product->price);

        return (float) $this->quantity * $price;
    }

    public function getQtyAttribute(): int
    {
        return (int) $this->quantity;
    }

    public function setQtyAttribute(int $value): void
    {
        $this->attributes['quantity'] = $value;
    }
}
