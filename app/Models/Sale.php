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
        'table_id',
        'user',
        'status',
        'validity',
    ];

    protected $attributes = [
        'total' => 0,
        'discount' => 0,
        'sd' => 0,
        'vat' => 0,
        'received' => 0,
        'change' => 0,
        'status' => 'active',
        'validity' => 1,
    ];

    use HasFactory;

    public function details()
    {
        return $this->hasMany(Saledetails::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}