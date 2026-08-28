<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = [
        'entrydate',
        'category_id',
        'product_name',
        'product_code',
        'cost_price',
        'pur_price',
        'last_price',
        'previous_price',
        'avg_price',
        'sale_price',
        'expire',
        'unit_id',
        'mfExStatus',
        'extra_status',
        'prdbelowrange',
        'imagepath',
        'user_id',
        'dis_status',
        'vat_rate',
        'sd_rate',
        'scharge',
        'product_type',
        'product_image',
        'opening_balance',
        'supplier_id',
        'food_type',
        'status',
        'validity',
    ];

    protected $casts = [
        'entrydate' => 'date',
        'cost_price' => 'decimal:3',
        'pur_price' => 'decimal:3',
        'last_price' => 'decimal:3',
        'previous_price' => 'decimal:3',
        'avg_price' => 'decimal:3',
        'sale_price' => 'decimal:3',
        'opening_balance' => 'decimal:3',
        'dis_status' => 'integer',
        'vat_rate' => 'integer',
        'sd_rate' => 'integer',
        'scharge' => 'decimal:3',
        'product_type' => 'integer',
        'food_type' => 'integer',
        'status' => 'integer',
        'validity' => 'integer',
    ];

    const PRODUCT_TYPE_SALE = 1;
    const PRODUCT_TYPE_RAW = 2;
    const PRODUCT_TYPE_SUB_RECIPE = 3;

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('validity', 1);
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product', 'product_id', 'supplier_id');
    }

    public function branchStores()
    {
        return $this->hasMany(BranchStore::class, 'product_id');
    }

    public function headOfficeStore()
    {
        return $this->hasOne(HeadOfficeStore::class, 'product_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (is_null($product->status)) {
                $product->status = 1;
            }
            if (is_null($product->validity)) {
                $product->validity = 1;
            }
            if (is_null($product->entrydate)) {
                $product->entrydate = now()->format('Y-m-d');
            }
            if (is_null($product->avg_price)) {
                $product->avg_price = $product->pur_price ?? 0;
            }
            if (is_null($product->user_id)) {
                $product->user_id = auth()->id();
            }
        });
    }
}
