<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'product_name',
        'category_id',
        'product_type',
        'price',
        'product_code',
        'unit',
        'vat',
        'sd',
        'validity',
    ];

    protected $casts = [
        'validity' => 'boolean',
        'price' => 'decimal:2',
        'vat' => 'decimal:2',
        'sd' => 'decimal:2',
    ];

    /**
     * Get the category that owns the product.
     * Using category_models table
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit');
    }

    public function saleDetails()
    {
        return $this->hasMany(Saledetails::class);
    }

    public function branchStore()
    {
        return $this->hasOne(BranchStore::class);
    }

    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }

    public function isValid()
    {
        return $this->validity == 1;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (is_null($product->validity)) {
                $product->validity = 1;
            }
            if (is_null($product->vat)) {
                $product->vat = 0;
            }
            if (is_null($product->sd)) {
                $product->sd = 0;
            }
        });
    }
}