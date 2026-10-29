<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'branch_id',
        'phone',
        'status',
        'last_login_at',
        'manager_pin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shifts()
    {
        return $this->hasMany(CashierShift::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot('branch_id');
    }

    public function isShopOwner(): bool
    {
        return in_array($this->role, ['shop_owner', 'business_owner', 'owner']);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'branch_manager', 'head_office_manager']);
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isInventoryStaff(): bool
    {
        return in_array($this->role, ['inventory_staff', 'inventory']);
    }

    public function isWarehouseManager(): bool
    {
        return in_array($this->role, ['warehouse_manager', 'warehouse']);
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    public function isHeadOfficeManager(): bool
    {
        return $this->role === 'head_office_manager';
    }

    public function getRoleDisplayNameAttribute(): string
    {
        return match($this->role) {
            'super_admin' => 'Super Administrator',
            'admin' => 'System Administrator',
            'shop_owner', 'business_owner', 'owner' => 'Shop Owner (Business Admin)',
            'manager', 'branch_manager' => 'Store Manager',
            'head_office_manager' => 'Head Office Manager',
            'cashier' => 'Front Desk Cashier',
            'inventory_staff', 'inventory' => 'Inventory Staff',
            'warehouse_manager', 'warehouse' => 'Warehouse Manager',
            'accountant' => 'Accountant',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    public function ownedBranches()
    {
        return $this->hasMany(Branch::class, 'owner_id');
    }

    public function shopSubscription()
    {
        return $this->hasOne(Subscription::class, 'shop_owner_id')->latestOfMany();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'sales_agent_id');
    }
}
