<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'expense_category_id',
        'amount',
        'reference_no',
        'expense_date',
        'notes',
        'user_id',
        'attachment',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

