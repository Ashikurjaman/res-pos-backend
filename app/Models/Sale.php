<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'entryDate',
        'invoiceNo',
        'discount',
        'sd',
        'vat',
        'total',
        'received',
        'change',
        'paymentMode',
        'user',
        'validity',
    ];
    use HasFactory;
    public function details()
    {
        return $this->hasMany(SaleDetails::class);
    }
}
