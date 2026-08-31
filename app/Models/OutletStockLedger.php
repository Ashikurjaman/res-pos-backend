<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletStockLedger extends Model
{
    protected $fillable = [
        'entry_date',
        'outlet_id',
        'product_id',
        'table_name',
        'unique_id',
        'in_qty',
        'out_qty',
        'balance_before',
        'balance_after',
        'type',
        'user_id',
        'remarks',
        'validity',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'in_qty' => 'decimal:3',
        'out_qty' => 'decimal:3',
        'balance_before' => 'decimal:3',
        'balance_after' => 'decimal:3',
    ];

    const TYPE_IN = 1;

    const TYPE_OUT = 2;

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute()
    {
        return $this->type == self::TYPE_IN ? 'IN' : 'OUT';
    }

    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }
}
