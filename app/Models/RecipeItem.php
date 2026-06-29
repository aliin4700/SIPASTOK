<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'inventory_item_id',
        'qty',
        'unit',
        'waste_percentage',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'waste_percentage' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function getEffectiveQtyAttribute(): float
    {
        return (float) $this->qty + ((float) $this->qty * (float) $this->waste_percentage / 100);
    }
}
