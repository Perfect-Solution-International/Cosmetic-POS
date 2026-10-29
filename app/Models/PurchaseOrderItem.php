<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_variation_id',
        'quantity_ordered',
        'free_quantity',
        'quantity_received',
        'unit_cost',
        'discount_percent',
        'tax_percent',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function getBackorderQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity_ordered - (int) $this->quantity_received);
    }

    public function getEffectiveUnitCostAttribute(): float
    {
        $totalReceivedOrOrdered = $this->quantity_ordered + $this->free_quantity;
        if ($totalReceivedOrOrdered <= 0) {
            return (float) $this->unit_cost;
        }
        return round((float) $this->subtotal / $totalReceivedOrOrdered, 2);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

