<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierCashMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_shift_id',
        'outlet_id',
        'cashier_id',
        'type',
        'category',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
