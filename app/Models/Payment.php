<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cashier_shift_id',
        'payment_method',
        'payment_channel',
        'payment_status',
        'paid_amount',
        'change_amount',
        'payment_proof',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_snap_token',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }
}
