<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    use HasFactory;

    public const VARIANCE_REASONS = [
        'shrinkage' => 'Susut Alami / Penyusutan Bahan',
        'unrecorded_waste' => 'Bahan Rusak/Terbuang Belum Tercatat',
        'recipe_variance' => 'Selisih Takaran Produksi / Resep',
        'admin_error' => 'Kesalahan Input Transaksi Sebelumnya',
        'missing_item' => 'Kehilangan / Selisih Tanpa Keterangan',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'stock_opname_id',
        'inventory_item_id',
        'system_stock',
        'physical_stock',
        'difference_qty',
        'unit_cost',
        'variance_value',
        'unit',
        'variance_reason',
        'notes',
    ];

    protected $casts = [
        'system_stock' => 'decimal:2',
        'physical_stock' => 'decimal:2',
        'difference_qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'variance_value' => 'decimal:2',
    ];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
