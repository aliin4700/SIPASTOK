<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    public const TYPES = ['raw_material', 'wip', 'finished_goods', 'packaging'];

    protected $fillable = [
        'division_id',
        'name',
        'code',
        'esb_item_code',
        'esb_item_name',
        'item_type',
        'base_unit',
        'purchase_unit',
        'conversion_value',
        'unit_conversion_factor',
        'unit_cost',
        'min_stock',
        'max_stock',
        'lead_time_days',
        'default_supplier_name',
        'last_purchase_price',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'conversion_value' => 'decimal:4',
        'unit_conversion_factor' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceiptItems()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function outputRecipes()
    {
        return $this->hasMany(Recipe::class, 'output_inventory_item_id');
    }
}
