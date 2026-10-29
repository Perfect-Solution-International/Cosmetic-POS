<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'purchase_cost',
        'selling_price',
        'wholesale_price',
        'reorder_level',
        'track_serial',
        'warranty_months',
        'image',
        'is_active',
    ];

    protected $casts = [
        'track_serial' => 'boolean',
        'warranty_months' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variation_values');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function stockForBranch($branchId)
    {
        return $this->stocks()->where('branch_id', $branchId)->sum('quantity');
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function availableSerialsForBranch($branchId)
    {
        return $this->serials()->where('branch_id', $branchId)->where('status', 'in_stock')->get();
    }

    public function quotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function stockTakeItems()
    {
        return $this->hasMany(StockTakeItem::class);
    }
}

