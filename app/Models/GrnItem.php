<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_variation_id',
        'batch_number',
        'mfg_date',
        'exp_date',
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'mfg_date' => 'date',
            'exp_date' => 'date',
        ];
    }

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

