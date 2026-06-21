<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'cashier_id',
        'shift_code',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashierCashMovement::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cashInTotal(): float
    {
        return (float) $this->cashMovements()
            ->where('type', 'cash_in')
            ->where('category', '!=', 'opening_cash')
            ->sum('amount');
    }

    public function cashOutTotal(): float
    {
        return (float) $this->cashMovements()
            ->where('type', 'cash_out')
            ->sum('amount');
    }

    public function expectedCashAmount(): float
    {
        return (float) $this->opening_cash + $this->cashInTotal() - $this->cashOutTotal();
    }

    public function refreshExpectedCash(): void
    {
        $this->update([
            'expected_cash' => $this->expectedCashAmount(),
        ]);
    }

    public function cashSalesTotal(): float
    {
        return (float) $this->cashMovements()
            ->where('type', 'cash_in')
            ->where('category', 'sales_cash')
            ->sum('amount');
    }

    public function extraCashInTotal(): float
    {
        return (float) $this->cashMovements()
            ->where('type', 'cash_in')
            ->whereNotIn('category', ['opening_cash', 'sales_cash'])
            ->sum('amount');
    }

    public function posSalesTotal(): float
    {
        return (float) $this->orders()
            ->where('order_channel', 'cashier_pos')
            ->sum('total_price');
    }

    public function paymentTotal(string $method): float
    {
        return (float) $this->payments()
            ->where('payment_method', $method)
            ->sum('paid_amount');
    }
}
