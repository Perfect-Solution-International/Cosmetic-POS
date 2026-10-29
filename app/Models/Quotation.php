<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_no',
        'branch_id',
        'user_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'date',
        'valid_until',
        'status',
        'pricing_mode',
        'subtotal',
        'discount_type',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'grand_total',
        'notes',
        'terms',
        'converted_sale_id',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedSale()
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }
}

