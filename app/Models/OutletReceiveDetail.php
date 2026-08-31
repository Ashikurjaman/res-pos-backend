<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletReceiveDetail extends Model
{
    protected $fillable = [
        'receive_id',
        'despatch_detail_id',
        'product_id',
        'despatched_qty',
        'received_qty',
        'short_qty',
        'damage_qty',
        'remarks',
        'validity'
    ];

    protected $casts = [
        'despatched_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'short_qty' => 'decimal:3',
        'damage_qty' => 'decimal:3'
    ];

    public function receive()
    {
        return $this->belongsTo(OutletReceive::class, 'receive_id');
    }

    public function despatchDetail()
    {
        return $this->belongsTo(OutletDespatchDetail::class, 'despatch_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
