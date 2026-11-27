<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionRecordItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_record_id',
        'inventory_item_id',
        'item_name',
        'item_type',
        'qty',
        'unit',
        'estimated_value',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'estimated_value' => 'decimal:2',
    ];

    public function productionRecord()
    {
        return $this->belongsTo(ProductionRecord::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
