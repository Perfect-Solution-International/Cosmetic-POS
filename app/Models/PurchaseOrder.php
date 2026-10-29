<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'branch_id',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'payment_terms',
        'payment_due_date',
        'notes',
        'delivery_instructions',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'payment_due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function getTotalOrderedQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity_ordered');
    }

    public function getTotalReceivedQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity_received');
    }

    public function getTotalFreeQuantityAttribute(): int
    {
        return (int) $this->items->sum('free_quantity');
    }

    public function getTotalBackorderQuantityAttribute(): int
    {
        return (int) $this->items->sum(function ($item) {
            return max(0, $item->quantity_ordered - $item->quantity_received);
        });
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-slate-500/20 text-slate-300 border-slate-500/40',
            'submitted' => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
            'approved' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
            'partially_received' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
            'completed' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
            'cancelled' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
            default => 'bg-carbon-800 text-silver-300 border-carbon-700',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft Order',
            'submitted' => 'Pending Approval',
            'approved' => 'Approved (Ready to Dispatch)',
            'partially_received' => 'Partially Received (Backorder)',
            'completed' => 'Completed / Closed',
            'cancelled' => 'Cancelled / Rejected',
            default => ucfirst($this->status),
        };
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function grns()
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

