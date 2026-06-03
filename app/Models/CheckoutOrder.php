<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutOrder extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending',
        'paid',
        'processed',
        'shipped',
        'completed',
        'cancelled',
    ];

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'shipping_address',
        'shipping_postal_code',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CheckoutOrderItem::class, 'order_id');
    }

    public function normalizedStatus(): string
    {
        return in_array($this->status, self::STATUSES, true) ? $this->status : 'pending';
    }

    public function paymentStatus(): string
    {
        if ($this->normalizedStatus() === 'cancelled') {
            return 'cancelled';
        }

        return in_array($this->normalizedStatus(), ['paid', 'processed', 'shipped', 'completed'], true)
            ? 'paid'
            : 'pending';
    }
}
