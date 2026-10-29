<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'opening_balance',
        'current_balance',
        'payment_terms',
        'notes',
        'status',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function grns()
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}

