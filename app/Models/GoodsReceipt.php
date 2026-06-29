<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'submitted', 'cancelled', 'applied'];

    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'supplier_id',
        'outlet_id',
        'received_by',
        'receipt_date',
        'status',
        'notes',
        'applied_at',
        'applied_by',
        'cancelled_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'applied_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
