<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'payment_method',
        'amount',
        'tender_amount',
        'change_amount',
        'card_ref',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}

