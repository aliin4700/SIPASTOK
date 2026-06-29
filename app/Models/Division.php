<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function userAccesses()
    {
        return $this->hasMany(UserDivisionAccess::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function divisionDailyOperations()
    {
        return $this->hasMany(DivisionDailyOperation::class);
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function wasteRecords()
    {
        return $this->hasMany(WasteRecord::class);
    }

    public function productionRecords()
    {
        return $this->hasMany(ProductionRecord::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }
}
