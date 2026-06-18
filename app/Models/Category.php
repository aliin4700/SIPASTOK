<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->hasMany(Product::class);
    }

    public function getNamaKategoriAttribute(): ?string
    {
        return $this->name;
    }

    public function setNamaKategoriAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }
}
