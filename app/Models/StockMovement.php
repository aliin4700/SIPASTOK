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
        'waste_out',
        'production_in',
        'production_out',
        'production_material_out',
        'stock_opname_adjustment',
        'manual_adjustment',
        'transfer_in',
        'transfer_out',
        'return_in',
        'esb_sales_out',
    ];

    public const LABELS = [
        'purchase_in' => 'Pembelian (PO / GR)',
        'material_issue_out' => 'Pengeluaran Bahan',
        'waste_out' => 'Waste / Susut',
        'production_in' => 'Hasil Produksi (WIP)',
        'production_out' => 'Produksi Keluar',
        'production_material_out' => 'Pemakaian Bahan BOM',
        'stock_opname_adjustment' => 'Penyesuaian Stock Opname',
        'manual_adjustment' => 'Penyesuaian Manual',
        'transfer_in' => 'Stok In',
        'transfer_out' => 'Transfer Keluar',
        'return_in' => 'Retur Masuk',
        'esb_sales_out' => 'Pemakaian Penjualan ESB',
    ];

    public function getMovementTypeLabelAttribute(): string
    {
        return self::LABELS[$this->movement_type] ?? str_replace('_', ' ', (string) $this->movement_type);
    }

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
