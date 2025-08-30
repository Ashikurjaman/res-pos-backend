<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchStore extends Model
{
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
        'sd'
    ];
    use HasFactory;
}
