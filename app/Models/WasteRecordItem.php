<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteRecordItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'waste_record_id',
        'inventory_item_id',
        'item_name',
        'item_type',
        'qty',
        'unit',
        'estimated_value',
        'reason',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'estimated_value' => 'decimal:2',
    ];

    public function wasteRecord()
    {
        return $this->belongsTo(WasteRecord::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
