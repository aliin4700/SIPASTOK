<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Kategori::class, 'category_id');
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return $this->fallbackImageUrl();
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        $image = trim(str_replace('\\', '/', $this->image), '/');
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
}
