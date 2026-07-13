<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Produk jualan BreadLab yang tampil di katalog dan POS.
 */
class Product extends Model
{
    use HasFactory;

    // Kolom yang dapat diisi dari form backend produk.
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'weight',
        'main_photo',
        'image',
        'status',
    ];

    // Cast harga/stok/berat agar perhitungan cart dan checkout konsisten.
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'weight' => 'integer',
    ];

    public function category()
    {
        // Produk berada dalam satu kategori katalog.
        return $this->belongsTo(Category::class);
    }

    public function photos()
    {
        // Foto tambahan produk selain main_photo.
        return $this->hasMany(ProductPhoto::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function getImageUrlAttribute(): string
    {
        // Normalisasi path gambar lama/baru sebelum dikirim ke view.
        if (! $this->main_photo) {
            return $this->fallbackImageUrl();
        }

        if (filter_var($this->main_photo, FILTER_VALIDATE_URL)) {
            return $this->main_photo;
        }

        $image = trim(str_replace('\\', '/', $this->main_photo), '/');
        $image = preg_replace('#^(public/)?storage/#', '', $image);
        $paths = str_contains($image, '/')
            ? [$image]
            : ['products/' . $image];

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path) || file_exists(public_path('storage/' . $path))) {
                return asset('storage/' . $path);
            }
        }

        return $this->fallbackImageUrl();
    }

    private function fallbackImageUrl(): string
    {
        if (file_exists(public_path('images/placeholder.png'))) {
            return asset('images/placeholder.png');
        }

        return asset('breadlab/logo.png');
    }

    public function getImageAttribute(): ?string
    {
        return $this->main_photo;
    }

    public function setImageAttribute(?string $value): void
    {
        $this->attributes['main_photo'] = $value;
    }
}
