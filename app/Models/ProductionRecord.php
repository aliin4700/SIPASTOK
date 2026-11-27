<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionRecord extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'approved', 'applied', 'cancelled'];
    public const TYPES = ['wip', 'finished_goods', 'prep'];

    protected $fillable = [
        'production_number',
        'division_daily_operation_id',
        'outlet_id',
        'division_id',
        'produced_by',
        'production_date',
        'production_type',
        'status',
        'notes',
        'submitted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'production_date' => 'date',
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

    public function producer()
    {
        return $this->belongsTo(User::class, 'produced_by');
    }

    public function items()
    {
        return $this->hasMany(ProductionRecordItem::class);
    }
}
