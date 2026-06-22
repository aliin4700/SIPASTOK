<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'current_stock',
        'reserved_stock',
        'available_stock',
        'unit',
        'last_stock_opname_at',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'reserved_stock' => 'decimal:2',
        'available_stock' => 'decimal:2',
        'last_stock_opname_at' => 'datetime',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
