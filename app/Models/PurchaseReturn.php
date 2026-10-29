<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_no',
        'supplier_id',
        'grn_id',
        'branch_id',
        'total_amount',
        'reason',
        'credit_note_no',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn()
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

