<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'product_variation_id',
        'batch_number',
        'exp_date',
        'quantity',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'exp_date' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

