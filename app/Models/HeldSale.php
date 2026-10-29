<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeldSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'branch_id',
        'register_id',
        'cashier_id',
        'customer_id',
        'customer_name',
        'discount_type',
        'discount_amount',
        'tax_rate',
        'subtotal',
        'grand_total',
        'note',
        'status',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(HeldSaleItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

