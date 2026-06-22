<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_request_id',
        'inventory_item_id',
        'requested_qty',
        'approved_qty',
        'issued_qty',
        'received_qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'requested_qty' => 'decimal:2',
        'approved_qty' => 'decimal:2',
        'issued_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
    ];

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
