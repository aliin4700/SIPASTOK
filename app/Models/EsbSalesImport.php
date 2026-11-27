<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsbSalesImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'report_date',
        'report_period',
        'file_name',
        'file_hash',
        'total_rows',
        'matched_rows',
        'processed_items',
        'skipped_rows',
        'imported_by',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', self::class);
    }
}
