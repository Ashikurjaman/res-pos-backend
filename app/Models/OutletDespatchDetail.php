<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletDespatchDetail extends Model
{
    protected $fillable = [
        'despatch_id',
        'request_detail_id',
        'product_id',
        'unit_id',
        'despatch_qty',
        'purchase_price',
        'total_amount',
        'remarks',
        'validity'
    ];

    protected $casts = [
        'despatch_qty' => 'decimal:3',
        'purchase_price' => 'decimal:3',
        'total_amount' => 'decimal:3'
    ];

    public function despatch()
    {
        return $this->belongsTo(OutletDespatch::class, 'despatch_id');
    }

    public function requestDetail()
    {
        return $this->belongsTo(OutletRequestDetail::class, 'request_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function receiveDetails()
    {
        return $this->hasMany(OutletReceiveDetail::class, 'despatch_detail_id');
    }

    public function getReceivedQtyAttribute()
    {
        return $this->receiveDetails()->sum('received_qty');
    }

    public function getIsFullyReceivedAttribute()
    {
        return $this->received_qty >= $this->despatch_qty;
    }

    public function getRemainingToReceiveAttribute()
    {
        return $this->despatch_qty - $this->received_qty;
    }
}
