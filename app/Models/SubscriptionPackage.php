<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'price',
        'billing_cycle',
        'included_branches',
        'price_per_additional_branch',
        'max_branches',
        'default_trial_days',
        'description',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'included_branches' => 'integer',
        'price_per_additional_branch' => 'decimal:2',
        'max_branches' => 'integer',
        'default_trial_days' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'package_id');
    }

    public function getActiveSubscriptionsCountAttribute(): int
    {
        return $this->subscriptions()->whereIn('status', ['trial', 'active'])->count();
    }
}

