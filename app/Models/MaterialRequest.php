<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'issued', 'received', 'cancelled'];

    protected $fillable = [
        'request_number',
        'division_daily_operation_id',
        'outlet_id',
        'division_id',
        'requested_by',
        'request_date',
        'status',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'submitted_at' => 'datetime',
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

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
}
