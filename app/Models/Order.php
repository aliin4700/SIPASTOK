<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Order final dari checkout online maupun cashier POS.
 */
class Order extends Model
{
    // Status operasional order; status pembayaran tetap dipisahkan di Payment.
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
        'outlet_id',
        'cashier_shift_id',
        'cashier_id',
        'order_number',
        'order_channel',
        'status',
        'transaction_method',
        'service_type',
        'customer_name',
        'customer_phone',
        'table_number',
        'pickup_time',
        'order_source',
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
        'pickup_time' => 'datetime',
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

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
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
        // Fallback menjaga tampilan admin tetap aman bila data lama punya status tidak dikenal.
        return in_array($this->status, self::STATUSES, true) ? $this->status : 'pending';
    }

    public function paymentStatus(): string
    {
        // Payment model menjadi sumber utama, orders.payment_status hanya ringkasan.
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
