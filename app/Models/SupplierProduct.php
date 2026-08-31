<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    use HasFactory;

    // Set this to the actual table name in your DB (from your dump it looks like 'supplier_product')
    protected $table = 'supplier_product';

    protected $fillable = [
        'supplier_id',
        'product_id',
        'purchase_price',
        'validity',
    ];

    // If your table uses timestamps, keep them; otherwise set to false
    public $timestamps = true;

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
