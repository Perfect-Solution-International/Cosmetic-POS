<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeldSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'held_sale_id',
        'product_variation_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function heldSale()
    {
        return $this->belongsTo(HeldSale::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

