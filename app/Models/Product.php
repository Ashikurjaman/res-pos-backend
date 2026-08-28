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
        'food_type_id', // ✅ Added for relationship
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
        'food_type_id' => 'integer',
        'status' => 'integer',
        'validity' => 'integer',
    ];

    // Constants
    const PRODUCT_TYPE_SALE = 1;
    const PRODUCT_TYPE_RAW = 2;
    const PRODUCT_TYPE_SUB_RECIPE = 3;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('validity', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByFoodType($query, $foodTypeId)
    {
        return $query->where('food_type_id', $foodTypeId);
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

    // ✅ Food Type Relationship
    public function foodType()
    {
        return $this->belongsTo(FoodType::class, 'food_type_id');
    }

    // Supplier Relationship (Many-to-Many)
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product', 'product_id', 'supplier_id')
                    ->withPivot('purchase_price')
                    ->withTimestamps();
    }

    // Branch Store Relationship
    public function branchStores()
    {
        return $this->hasMany(BranchStore::class, 'product_id');
    }

    public function branchStoreForOutlet($outletId)
    {
        return $this->hasOne(BranchStore::class, 'product_id')
                    ->where('outlet_id', $outletId);
    }

    // Head Office Store Relationship
    public function headOfficeStore()
    {
        return $this->hasOne(HeadOfficeStore::class, 'product_id');
    }

    // Head Office Stock Relationship
    public function headOfficeStocks()
    {
        return $this->hasMany(HeadOfficeStock::class, 'product_id');
    }

    // Sale Details Relationship
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'product_id');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return $this->status == self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    public function getProductTypeLabelAttribute()
    {
        $types = [
            self::PRODUCT_TYPE_SALE => 'Sale Product',
            self::PRODUCT_TYPE_RAW => 'Raw Materials',
            self::PRODUCT_TYPE_SUB_RECIPE => 'Sub Recipe',
        ];
        return $types[$this->product_type] ?? 'Unknown';
    }

    public function getFullNameAttribute()
    {
        return $this->product_name . ' (' . $this->product_code . ')';
    }

    public function getProfitAttribute()
    {
        return ($this->sale_price ?? 0) - ($this->pur_price ?? 0);
    }

    public function getProfitMarginAttribute()
    {
        if ($this->pur_price > 0) {
            return (($this->sale_price - $this->pur_price) / $this->pur_price) * 100;
        }
        return 0;
    }

    // Check if product is in stock
    public function getTotalStockAttribute()
    {
        return $this->branchStores()->sum('balanceinhand') ?? 0;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (is_null($product->status)) {
                $product->status = self::STATUS_ACTIVE;
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
            if (is_null($product->food_type)) {
                $product->food_type = 0;
            }
        });

        static::updating(function ($product) {
            // Track price changes
            if ($product->isDirty('pur_price')) {
                $product->previous_price = $product->getOriginal('pur_price');
                $product->avg_price = $product->pur_price;
                $product->last_price = $product->pur_price;
            }
        });

        static::deleting(function ($product) {
            // Delete related records
            $product->branchStores()->delete();
            $product->headOfficeStocks()->delete();
            $product->suppliers()->detach();
        });
    }
}
