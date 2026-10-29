<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTake extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'branch_id',
        'user_id',
        'status',
        'started_at',
        'completed_at',
        'total_system_qty',
        'total_physical_qty',
        'total_variance_qty',
        'total_variance_cost',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_variance_cost' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockTakeItem::class);
    }
}

