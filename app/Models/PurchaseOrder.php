<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'cancelled', 'received', 'partially_received'];

    protected $fillable = [
        'po_number',
        'supplier_id',
        'outlet_id',
        'ordered_by',
        'order_date',
        'expected_date',
        'status',
        'notes',
        'submitted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}
