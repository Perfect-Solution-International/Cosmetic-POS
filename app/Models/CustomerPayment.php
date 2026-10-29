<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_no',
        'customer_id',
        'branch_id',
        'amount',
        'payment_method',
        'reference_no',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'card' => 'Credit / Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }
}

