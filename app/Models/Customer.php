<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_id',
        'google_token',
        'address',
        'alamat',
        'postal_code',
        'pos',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function getAlamatAttribute(): ?string
    {
        return $this->address;
    }

    public function setAlamatAttribute(?string $value): void
    {
        $this->attributes['address'] = $value;
    }

    public function getPosAttribute(): ?string
    {
        return $this->postal_code;
    }

    public function setPosAttribute(?string $value): void
    {
        $this->attributes['postal_code'] = $value;
    }
}
