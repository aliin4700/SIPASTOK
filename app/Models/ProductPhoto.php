<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Foto tambahan untuk satu produk.
 */
class ProductPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'photo',
        'foto',
    ];

    public function product()
    {
        // Foto selalu melekat pada produk induknya.
        return $this->belongsTo(Product::class);
    }

    public function getFotoAttribute(): ?string
    {
        // Alias lama `foto` dipertahankan untuk kompatibilitas view lama.
        return $this->photo;
    }

    public function setFotoAttribute(?string $value): void
    {
        $this->attributes['photo'] = $value;
    }
}
