<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_code',
        'name',
        'product_id',
        'output_inventory_item_id',
        'output_qty',
        'output_unit',
        'division_id',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'output_qty' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function outputInventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'output_inventory_item_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }
}
