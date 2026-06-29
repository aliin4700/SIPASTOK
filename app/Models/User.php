<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nama',
        'email',
        'role',
        'status',
        'password',
        'phone',
        'hp',
        'photo',
        'foto',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function adminConversations()
    {
        return $this->hasMany(Conversation::class, 'admin_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function roleRecord()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function outletAccesses()
    {
        return $this->hasMany(UserOutletAccess::class);
    }

    public function divisionAccesses()
    {
        return $this->hasMany(UserDivisionAccess::class);
    }

    public function cashierShifts()
    {
        return $this->hasMany(CashierShift::class, 'cashier_id');
    }

    public function cashierCashMovements()
    {
        return $this->hasMany(CashierCashMovement::class, 'cashier_id');
    }

    public function createdSalesTargets()
    {
        return $this->hasMany(SalesTarget::class, 'created_by');
    }

    public function recordedOperationalExpenses()
    {
        return $this->hasMany(OperationalExpense::class, 'recorded_by');
    }

    public function approvedOperationalExpenses()
    {
        return $this->hasMany(OperationalExpense::class, 'approved_by');
    }

    public function openedDivisionDailyOperations()
    {
        return $this->hasMany(DivisionDailyOperation::class, 'opened_by');
    }

    public function submittedDivisionDailyOperations()
    {
        return $this->hasMany(DivisionDailyOperation::class, 'submitted_by');
    }

    public function approvedDivisionDailyOperations()
    {
        return $this->hasMany(DivisionDailyOperation::class, 'approved_by');
    }

    public function createdStockMovements()
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'requested_by');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'ordered_by');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class, 'received_by');
    }

    public function appliedGoodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class, 'applied_by');
    }

    public function wasteRecords()
    {
        return $this->hasMany(WasteRecord::class, 'recorded_by');
    }

    public function productionRecords()
    {
        return $this->hasMany(ProductionRecord::class, 'produced_by');
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class, 'counted_by');
    }

    public function reviewedStockOpnames()
    {
        return $this->hasMany(StockOpname::class, 'reviewed_by');
    }

    public function hasRole(string $role): bool
    {
        return (string) $this->role === $role;
    }

    public function hasAnyRole(array|string $roles): bool
    {
        return in_array((string) $this->role, (array) $roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roleRecord()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    public function hasOutletAccess(int|string $outletId, ?string $ability = null): bool
    {
        if ($this->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $this->outletAccesses()
            ->where('outlet_id', $outletId)
            ->when($ability, fn ($query) => $query->where($ability, true))
            ->exists();
    }

    public function hasDivisionAccess(int|string $outletId, int|string $divisionId, ?string $ability = null): bool
    {
        if ($this->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $this->divisionAccesses()
            ->where('outlet_id', $outletId)
            ->where('division_id', $divisionId)
            ->when($ability, fn ($query) => $query->where($ability, true))
            ->exists();
    }

    public function getNamaAttribute(): ?string
    {
        return $this->name;
    }

    public function setNamaAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getHpAttribute(): ?string
    {
        return $this->phone;
    }

    public function setHpAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
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
