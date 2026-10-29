<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shifts()
    {
        return $this->hasMany(CashierShift::class);
    }

    public function currentShift()
    {
        return $this->hasOne(CashierShift::class)->where('status', 'open')->latest();
    }
}

