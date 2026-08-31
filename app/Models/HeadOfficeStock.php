<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeadOfficeStock extends Model
{
    protected $table = 'head_office_stock';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'entry_date',
        'quantity',
        'purchase_price',
        'total_amount',
        'previous_balance',
        'current_balance',
        'status',
        'validity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'purchase_price' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'previous_balance' => 'decimal:3',
        'current_balance' => 'decimal:3',
        'entry_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
