<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'staff_salary',
        'electricity',
        'gas',
        'water',
        'rent',
        'internet',
        'packaging',
        'maintenance',
        'delivery_operational',
        'marketing',
        'cleaning',
        'equipment',
        'other',
    ];

    public const STATUSES = ['recorded', 'approved', 'rejected'];

    protected $fillable = [
        'outlet_id',
        'expense_date',
        'expense_month',
        'expense_year',
        'category',
        'description',
        'amount',
        'payment_method',
        'recorded_by',
        'approved_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
