<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saledetails extends Model
{
    use HasFactory;

    protected $table = 'saledetails';

    protected $fillable = [
        'sale_id',
        'invoiceNo',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'sd',
        'vat',
        'total',
        'category_id',
        'user',
        'validity',
    ];

    protected $casts = [
        'validity' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the category for the sale detail.
     * Using category_models table
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function scopeValid($query)
    {
        return $query->where('validity', 1);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($detail) {
            if (is_null($detail->validity)) {
                $detail->validity = 1;
            }
            if (is_null($detail->sd)) {
                $detail->sd = 0;
            }
            if (is_null($detail->vat)) {
                $detail->vat = 0;
            }
        });
    }
}