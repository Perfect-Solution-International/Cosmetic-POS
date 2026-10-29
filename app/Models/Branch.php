<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'type',
        'owner_id',
        'receipt_header',
        'receipt_footer',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function registers()
    {
        return $this->hasMany(Register::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function stockTakes()
    {
        return $this->hasMany(StockTake::class);
    }
}

