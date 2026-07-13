<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Data pembayaran untuk order online dan POS.
 */
class Payment extends Model
{
    use HasFactory;

    // Kolom payment manual, POS, dan placeholder Midtrans.
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

    // Nominal dan tanggal dibaca dengan tipe yang sesuai.
    protected $casts = [
        'paid_at' => 'datetime',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function order()
    {
        // Satu payment terhubung ke satu order.
        return $this->belongsTo(Order::class);
    }

    public function cashierShift()
    {
        // Terisi untuk pembayaran yang dibuat dari cashier POS.
        return $this->belongsTo(CashierShift::class);
    }
}
