<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchStore extends Model
{
    use HasFactory;

    protected $table = 'branch_stores';

    protected $fillable = [
        'product_id',
        'product_name',
        'category_id',
        'category_name',
        'product_type',
        'price',
        'prv_stock',
        'stock',
        'after_stock',
        'product_code',
        'unit',
        'vat',
        'sd',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
        'prv_stock' => 'integer',
        'stock' => 'integer',
        'after_stock' => 'integer',
        'product_code' => 'integer',
        'unit' => 'integer',
        'vat' => 'integer',
        'sd' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get the product that owns the branch store.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the category that owns the branch store.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Scope a query to only include active branch stores.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Check if branch store is active.
     */
    public function isActive()
    {
        return $this->status == 1;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($branchStore) {
            if (is_null($branchStore->status)) {
                $branchStore->status = 1;
            }
            if (is_null($branchStore->prv_stock)) {
                $branchStore->prv_stock = 0;
            }
            if (is_null($branchStore->stock)) {
                $branchStore->stock = 0;
            }
            if (is_null($branchStore->after_stock)) {
                $branchStore->after_stock = 0;
            }
            if (is_null($branchStore->vat)) {
                $branchStore->vat = 0;
            }
            if (is_null($branchStore->sd)) {
                $branchStore->sd = 0;
            }
        });
    }
}