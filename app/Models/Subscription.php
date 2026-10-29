<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_no',
        'business_name',
        'shop_owner_id',
        'customer_id',
        'package_id',
        'branch_id',
        'allocated_branches',
        'status',
        'is_free_trial',
        'trial_days',
        'trial_ends_at',
        'started_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'price',
        'base_price',
        'additional_branch_price',
        'total_price',
        'billing_cycle',
        'sales_channel',
        'sales_agent_id',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'is_free_trial' => 'boolean',
        'auto_renew' => 'boolean',
        'trial_days' => 'integer',
        'allocated_branches' => 'integer',
        'price' => 'decimal:2',
        'base_price' => 'decimal:2',
        'additional_branch_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'started_at' => 'datetime',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
    ];

    public function shopOwner()
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesAgent()
    {
        return $this->belongsTo(User::class, 'sales_agent_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'subscription_id');
    }

    /** Helper to check if free trial is currently ongoing */
    public function isTrialActive(): bool
    {
        return $this->is_free_trial 
            && $this->status === 'trial' 
            && $this->trial_ends_at 
            && $this->trial_ends_at->isFuture();
    }

    /** Remaining days in trial or period */
    public function getDaysRemainingAttribute(): int
    {
        $targetDate = $this->is_free_trial ? $this->trial_ends_at : $this->current_period_ends_at;
        if (!$targetDate) {
            return 0;
        }

        if ($targetDate->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($targetDate, false);
    }

    /** Formatted human-readable status badge */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isTrialActive()) {
            $days = $this->days_remaining;
            return "Free Trial ({$days}d left)";
        }

        return match ($this->status) {
            'trial' => 'Trial Expired',
            'active' => 'Paid Active',
            'past_due' => 'Payment Overdue',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }
}

