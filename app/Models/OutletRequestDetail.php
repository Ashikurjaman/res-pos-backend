<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletRequestDetail extends Model
{
    protected $fillable = [
        'request_id',
        'product_id',
        'unit_id',
        'requested_qty',
        'approved_qty',
        'despatched_qty',
        'received_qty',
        'remarks',
        'validity',
    ];

    protected $casts = [
        'requested_qty' => 'decimal:3',
        'approved_qty' => 'decimal:3',
        'despatched_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
    ];

    public function request()
    {
        return $this->belongsTo(OutletRequest::class, 'request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function despatchDetails()
    {
        return $this->hasMany(OutletDespatchDetail::class, 'request_detail_id');
    }

    public function getIsFullyDespatchedAttribute()
    {
        return $this->despatched_qty >= $this->approved_qty;
    }

    public function getIsFullyReceivedAttribute()
    {
        return $this->received_qty >= $this->despatched_qty;
    }

    public function getRemainingToDespatchAttribute()
    {
        return $this->approved_qty - $this->despatched_qty;
    }

    public function getRemainingToReceiveAttribute()
    {
        return $this->despatched_qty - $this->received_qty;
    }
}
