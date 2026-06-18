<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipping_type',
        'courier',
        'service',
        'tracking_number',
        'shipping_cost',
        'estimated_delivery',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_address',
        'delivery_note',
        'delivery_distance',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
        'delivery_distance' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
