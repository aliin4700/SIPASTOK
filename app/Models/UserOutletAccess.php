<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOutletAccess extends Model
{
    use HasFactory;

    protected $table = 'user_outlet_access';

    protected $fillable = [
        'user_id',
        'outlet_id',
        'can_monitor',
        'can_cashier',
        'can_manage_stock',
        'can_manage_purchase',
        'can_manage_finance',
    ];

    protected $casts = [
        'can_monitor' => 'boolean',
        'can_cashier' => 'boolean',
        'can_manage_stock' => 'boolean',
        'can_manage_purchase' => 'boolean',
        'can_manage_finance' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
