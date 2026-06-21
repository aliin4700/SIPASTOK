<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    use HasFactory;

    public const PERIOD_TYPES = ['daily', 'monthly', 'yearly'];

    protected $fillable = [
        'outlet_id',
        'period_type',
        'target_date',
        'target_month',
        'target_year',
        'target_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'target_date' => 'date',
        'target_amount' => 'decimal:2',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
