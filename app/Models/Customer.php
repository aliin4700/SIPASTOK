<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Profil customer yang terhubung ke akun users.
 */
class Customer extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi dari form account/register.
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
        // Satu customer dimiliki oleh satu user.
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function carts()
    {
        // Customer dapat memiliki riwayat cart, walau yang aktif biasanya satu.
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        // Semua order online customer.
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        // Address book customer untuk checkout shipping.
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
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
