<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'branch_id',
        'register_id',
        'customer_id',
        'cashier_id',
        'shift_id',
        'subtotal',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'change_amount',
        'loyalty_points_redeemed',
        'loyalty_discount_amount',
        'pricing_mode',
        'quotation_id',
        'sales_channel',
        'sales_agent_id',
        'subscription_id',
        'payment_status',
        'notes',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function salesAgent()
    {
        return $this->belongsTo(User::class, 'sales_agent_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function shift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }
}

