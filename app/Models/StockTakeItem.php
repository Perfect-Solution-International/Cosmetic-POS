<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTakeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_take_id',
        'product_variation_id',
        'system_qty',
        'physical_qty',
        'variance_qty',
        'unit_cost',
        'variance_cost',
        'scanned_at',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'variance_cost' => 'decimal:2',
        'scanned_at' => 'datetime',
    ];

    public function stockTake()
    {
        return $this->belongsTo(StockTake::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

