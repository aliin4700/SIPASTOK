<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kategori produk untuk katalog BreadLab.
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'nama_kategori',
    ];

    public function products()
    {
        // Satu kategori dapat memiliki banyak produk.
        return $this->hasMany(Product::class);
    }

    public function getNamaKategoriAttribute(): ?string
    {
        // Alias lama dipertahankan agar view lama tetap kompatibel.
        return $this->name;
    }

    public function setNamaKategoriAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }
}
