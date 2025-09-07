<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saledetails extends Model
{
    protected $fillable = [
        'entryDate',
        'sale_id',
        'invoiceNo',
        'product_name',
        'product_id',
        'quantity',
        'sd',
        'vat',
        'price',
        'total',
        'category_id',
        'user',
        'validity',
    ];
    use HasFactory;

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
