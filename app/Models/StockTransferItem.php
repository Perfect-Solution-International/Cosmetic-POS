<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'product_variation_id',
        'batch_number',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'discrepancy_reason',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

