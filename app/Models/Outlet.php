<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function userAccesses()
    {
        return $this->hasMany(UserOutletAccess::class);
    }

    public function divisionAccesses()
    {
        return $this->hasMany(UserDivisionAccess::class);
    }

    public function cashierShifts()
    {
        return $this->hasMany(CashierShift::class);
    }

    public function cashierCashMovements()
    {
        return $this->hasMany(CashierCashMovement::class);
    }

    public function salesTargets()
    {
        return $this->hasMany(SalesTarget::class);
    }

    public function operationalExpenses()
    {
        return $this->hasMany(OperationalExpense::class);
    }
}
