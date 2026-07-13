<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Alamat tersimpan customer untuk pengiriman marketplace-style.
 */
class CustomerAddress extends Model
{
    use HasFactory;

    // Data alamat disimpan lengkap sampai district agar cocok dengan ongkir Komerce.
    protected $fillable = [
        'customer_id',
        'receiver_name',
        'receiver_phone',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'postal_code',
        'full_address',
        'detail_address',
        'latitude',
        'longitude',
        'label',
        'is_default',
    ];

    // Cast menjaga flag default dan koordinat mudah dipakai di view.
    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function customer()
    {
        // Setiap alamat dimiliki oleh satu customer.
        return $this->belongsTo(Customer::class);
    }

    public function snapshotAddress(): string
    {
        // Snapshot dipakai saat checkout agar order tidak berubah jika address book diedit.
        return collect([
            $this->full_address,
            $this->detail_address,
            $this->district_name,
            $this->city_name,
            $this->province_name,
            $this->postal_code ? 'Kode Pos ' . $this->postal_code : null,
        ])->filter()->implode(', ');
    }
}
