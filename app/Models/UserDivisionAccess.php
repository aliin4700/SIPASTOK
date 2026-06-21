<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDivisionAccess extends Model
{
    use HasFactory;

    protected $table = 'user_division_access';

    protected $fillable = [
        'user_id',
        'outlet_id',
        'division_id',
        'can_input_stock',
        'can_input_waste',
        'can_request_order',
    ];

    protected $casts = [
        'can_input_stock' => 'boolean',
        'can_input_waste' => 'boolean',
        'can_request_order' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
