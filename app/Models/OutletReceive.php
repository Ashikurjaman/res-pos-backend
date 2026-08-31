<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletReceive extends Model
{
    protected $fillable = [
        'receive_no',
        'receive_date',
        'despatch_id',
        'receiving_outlet_id',
        'received_by',
        'status',
        'remarks',
        'validity',
    ];

    protected $casts = [
        'receive_date' => 'date',
    ];

    const STATUS_PENDING = 0;

    const STATUS_COMPLETE = 1;

    const STATUS_PARTIAL = 2;

    const STATUS_DISCREPANCY = 3;

    public function despatch()
    {
        return $this->belongsTo(OutletDespatch::class, 'despatch_id');
    }

    public function receivingOutlet()
    {
        return $this->belongsTo(Outlet::class, 'receiving_outlet_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function details()
    {
        return $this->hasMany(OutletReceiveDetail::class, 'receive_id');
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_DISCREPANCY => 'Discrepancy',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }
}
