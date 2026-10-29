<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'register_id',
        'user_id',
        'opening_float',
        'cash_sales',
        'card_sales',
        'other_sales',
        'expected_cash',
        'closing_cash',
        'cash_discrepancy',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }
}

