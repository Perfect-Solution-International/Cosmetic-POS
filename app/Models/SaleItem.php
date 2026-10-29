<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_variation_id',
        'batch_number',
        'quantity',
        'purchase_cost',
        'unit_price',
        'discount_amount',
        'subtotal',
        'serial_numbers',
        'is_exchange',
        'exchange_reason',
    ];

    protected $casts = [
        'serial_numbers' => 'array',
        'is_exchange' => 'boolean',
    ];

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

