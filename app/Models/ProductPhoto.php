<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsTo(Product::class);
    }

    public function getFotoAttribute(): ?string
    {
        return $this->photo;
    }

    public function setFotoAttribute(?string $value): void
    {
        $this->attributes['photo'] = $value;
    }
}
