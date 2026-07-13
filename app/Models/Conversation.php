<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Percakapan customer-admin yang selalu terhubung ke satu order.
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'admin_id',
        'status',
    ];

    public function order()
    {
        // Conversation dibuat per order.
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        // Customer pemilik conversation.
        return $this->belongsTo(Customer::class);
    }

    public function admin()
    {
        // Admin penanggung jawab terisi saat admin pertama membalas.
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function messages()
    {
        // Semua pesan dalam conversation.
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
