<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletRequest extends Model
{
    protected $fillable = [
        'request_no',
        'request_date',
        'requesting_outlet_id',
        'source_outlet_id',
        'request_type',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'remarks',
        'validity',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 0;

    const STATUS_APPROVED = 1;

    const STATUS_PARTIAL_APPROVED = 2;

    const STATUS_REJECTED = 3;

    const STATUS_DESPATCHED = 4;

    const STATUS_RECEIVED = 5;

    const STATUS_CLOSED = 6;

    const TYPE_HO_REQUEST = 1;

    const TYPE_OUTLET_TRANSFER = 2;

    public function requestingOutlet()
    {
        return $this->belongsTo(Outlet::class, 'requesting_outlet_id');
    }

    public function sourceOutlet()
    {
        return $this->belongsTo(Outlet::class, 'source_outlet_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details()
    {
        return $this->hasMany(OutletRequestDetail::class, 'request_id');
    }

    public function despatches()
    {
        return $this->hasMany(OutletDespatch::class, 'request_id');
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PARTIAL_APPROVED => 'Partial Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DESPATCHED => 'Despatched',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_CLOSED => 'Closed',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getTypeLabelAttribute()
    {
        $types = [
            self::TYPE_HO_REQUEST => 'HO Request',
            self::TYPE_OUTLET_TRANSFER => 'Outlet Transfer',
        ];

        return $types[$this->request_type] ?? 'Unknown';
    }

    // Scope for pending requests
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Scope for active requests
    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }
}
