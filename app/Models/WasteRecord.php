<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteRecord extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'cancelled', 'applied'];

    public const REASONS = [
        'expired',
        'over_production',
        'damaged',
        'wrong_process',
        'customer_return',
        'trial_menu',
        'human_error',
        'other',
    ];

    protected $fillable = [
        'waste_number',
        'division_daily_operation_id',
        'outlet_id',
        'division_id',
        'recorded_by',
        'waste_date',
        'status',
        'notes',
        'submitted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'waste_date' => 'date',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function dailyOperation()
    {
        return $this->belongsTo(DivisionDailyOperation::class, 'division_daily_operation_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function items()
    {
        return $this->hasMany(WasteRecordItem::class);
    }
}
