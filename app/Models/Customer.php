<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_group_id',
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'current_balance',
        'loyalty_points',
        'pricing_tier',
    ];

    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->whereIn('status', ['trial', 'active'])->latestOfMany();
    }

    public function getAvailableCreditAttribute(): float
    {
        return max(0, (float)$this->credit_limit - (float)$this->current_balance);
    }
}

