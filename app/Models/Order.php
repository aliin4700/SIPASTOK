<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = [
        'pending',
        'waiting_payment',
        'paid',
        'processing',
        'ready_for_pickup',
        'delivering',
        'shipped',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'transaction_method',
        'payment_method',
        'payment_status',
        'subtotal',
        'shipping_cost',
        'total_weight',
        'total_price',
        'address',
        'postal_code',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_weight' => 'integer',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Customer::class, 'id', 'id', 'customer_id', 'user_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function normalizedStatus(): string
    {
        return in_array($this->status, self::STATUSES, true) ? $this->status : 'pending';
    }

    public function paymentStatus(): string
    {
        return $this->payment?->payment_status ?? $this->payment_status ?? 'pending';
    }

    public function getShippingAddressAttribute(): ?string
    {
        return $this->address;
    }

    public function getShippingPostalCodeAttribute(): ?string
    {
        return $this->postal_code;
    }

    public function getShippingCourierAttribute(): ?string
    {
        return $this->shipment?->courier;
    }

    public function getShippingServiceAttribute(): ?string
    {
        return $this->shipment?->service;
    }

    public function getShippingEstimationAttribute(): ?string
    {
        return $this->shipment?->estimated_delivery;
    }

    public function getShippingTotalWeightAttribute(): int
    {
        return (int) $this->total_weight;
    }

    public function getShippingCityAttribute(): ?string
    {
        return null;
    }

    public function getShippingProvinceAttribute(): ?string
    {
        return null;
    }

    public function getPaymentReferenceAttribute(): ?string
    {
        return $this->payment?->midtrans_order_id;
    }
}
