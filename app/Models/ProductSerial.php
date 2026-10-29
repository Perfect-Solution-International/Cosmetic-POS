<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variation_id',
        'branch_id',
        'serial_number',
        'status',
        'purchase_cost',
        'sale_id',
        'sale_item_id',
        'sold_at',
        'warranty_months',
        'warranty_expires_at',
        'notes',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'warranty_expires_at' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function getIsUnderWarrantyAttribute()
    {
        if (!$this->warranty_expires_at) return false;
        return $this->warranty_expires_at->isFuture() || $this->warranty_expires_at->isToday();
    }
}

