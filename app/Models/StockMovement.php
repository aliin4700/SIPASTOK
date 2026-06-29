<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPES = [
        'purchase_in',
        'material_issue_out',
        'sales_out',
        'waste_out',
        'production_in',
        'production_out',
        'stock_opname_adjustment',
        'manual_adjustment',
        'transfer_in',
        'transfer_out',
        'return_in',
    ];

    protected $fillable = [
        'outlet_id',
        'division_id',
        'inventory_item_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'qty',
        'unit',
        'stock_before',
        'stock_after',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
