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
        'food_type_id',
        'dis_status',
        'vat_rate',
        'sd_rate',
        'scharge',
        'product_type',
        'product_image',
        'imagepath',
        'opening_balance',
        'supplier_id',
        'status',
        'user_id',
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
        'food_type_id' => 'integer',
        'status' => 'integer',
        'validity' => 'integer',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function foodType()
    {
        return $this->belongsTo(FoodType::class, 'food_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product', 'product_id', 'supplier_id')
                    ->withPivot('purchase_price')
                    ->withTimestamps();
    }

    public function branchStores()
    {
        return $this->hasMany(BranchStore::class, 'product_id');
    }

    public function headOfficeStore()
    {
        return $this->hasOne(HeadOfficeStore::class, 'product_id');
    }

    public function headOfficeStocks()
    {
        return $this->hasMany(HeadOfficeStock::class, 'product_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('validity', 1);
    }

    public function scopeRawMaterial($query)
    {
        return $query->where('product_type', 2);
    }

    public function scopeSaleProduct($query)
    {
        return $query->where('product_type', 1);
    }

    // Accessors
    public function getProductTypeLabelAttribute()
    {
        $types = [
            1 => 'Sale Product',
            2 => 'Raw Material',
            3 => 'Sub Recipe',
        ];
        return $types[$this->product_type] ?? 'Unknown';
    }

    public function getStatusLabelAttribute()
    {
        return $this->status == 1 ? 'Active' : 'Inactive';
    }

    // Boot method
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
            if (is_null($product->user_id)) {
                $product->user_id = auth()->id();
            }
            if (is_null($product->avg_price)) {
                $product->avg_price = $product->pur_price ?? 0;
            }
            if ($product->product_type == 2 && is_null($product->last_price)) {
                $product->last_price = $product->pur_price ?? 0;
            }
        });
    }
}
