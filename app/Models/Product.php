<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category_id',
        'subcategory_id',
        'brand_id',
        'type',
        'has_expiry',
        'description',
        'image',
        'discount_percentage',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }
}

