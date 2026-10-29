<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_no',
        'branch_id',
        'product_variation_id',
        'batch_number',
        'type',
        'quantity',
        'reason',
        'adjusted_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}

