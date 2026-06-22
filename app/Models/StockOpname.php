<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'cancelled', 'reviewed'];

    protected $fillable = [
        'opname_number',
        'division_daily_operation_id',
        'outlet_id',
        'division_id',
        'counted_by',
        'opname_date',
        'opname_type',
        'status',
        'notes',
        'submitted_at',
        'cancelled_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reviewed_at' => 'datetime',
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

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
