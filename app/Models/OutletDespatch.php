<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletDespatch extends Model
{
    protected $fillable = [
        'despatch_no',
        'despatch_date',
        'request_id',
        'source_type',
        'source_outlet_id',
        'dest_outlet_id',
        'vehicle_no',
        'driver_name',
        'despatched_by',
        'status',
        'total_qty',
        'total_amount',
        'remarks',
        'validity',
    ];

    protected $casts = [
        'despatch_date' => 'date',
        'total_qty' => 'decimal:3',
        'total_amount' => 'decimal:3',
    ];

    const SOURCE_HEAD_OFFICE = 1;

    const SOURCE_OUTLET = 2;

    const STATUS_PENDING = 0;

    const STATUS_IN_TRANSIT = 1;

    const STATUS_RECEIVED = 2;

    const STATUS_PARTIAL_RECEIVED = 3;

    const STATUS_CANCELLED = 4;

    public function request()
    {
        return $this->belongsTo(OutletRequest::class, 'request_id');
    }

    public function sourceOutlet()
    {
        return $this->belongsTo(Outlet::class, 'source_outlet_id');
    }

    public function destOutlet()
    {
        return $this->belongsTo(Outlet::class, 'dest_outlet_id');
    }

    public function despatchedBy()
    {
        return $this->belongsTo(User::class, 'despatched_by');
    }

    public function details()
    {
        return $this->hasMany(OutletDespatchDetail::class, 'despatch_id');
    }

    public function receives()
    {
        return $this->hasMany(OutletReceive::class, 'despatch_id');
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_PARTIAL_RECEIVED => 'Partial Received',
            self::STATUS_CANCELLED => 'Cancelled',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getSourceTypeLabelAttribute()
    {
        $types = [
            self::SOURCE_HEAD_OFFICE => 'Head Office',
            self::SOURCE_OUTLET => 'Outlet',
        ];

        return $types[$this->source_type] ?? 'Unknown';
    }

    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }
}
