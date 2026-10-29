<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceivedNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'supplier_invoice_no',
        'received_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'payment_method',
        'payment_reference',
        'paid_date',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'paid_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) ($this->paid_amount ?? 0));
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            'partially_paid' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
            default => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'cheque' => 'Cheque',
            'credit_card' => 'Credit Card',
            default => $this->payment_method ? ucfirst(str_replace('_', ' ', $this->payment_method)) : 'Pending',
        };
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
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
        return $this->hasMany(GrnItem::class, 'grn_id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'grn_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}

