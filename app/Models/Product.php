<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name',
        'category',
        'product_type',
        'price',
        'product_code',
        'unit',
        'vat',
        'sd'
    ];
}
