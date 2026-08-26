<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'outlets';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entrydate',
        'outlet_code',
        'outlet_name',
        'short_name',
        'outlet_address',
        'outlet_mgr',
        'mgr_contact_no',
        'ho_mobile_no',
        'status',
        'vat_reg_no_old',
        'vat_reg_no_new',
        'validity',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'integer',
        'validity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const VALIDITY_ACTIVE = 1;
    const VALIDITY_INACTIVE = 0;

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('validity', self::VALIDITY_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeValid($query)
    {
        return $query->where('validity', self::VALIDITY_ACTIVE);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return $this->status === self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    public function getValidityLabelAttribute()
    {
        return $this->validity === self::VALIDITY_ACTIVE ? 'Valid' : 'Invalid';
    }

    public function getFullAddressAttribute()
    {
        return $this->outlet_address . ' (Manager: ' . $this->outlet_mgr . ')';
    }

    // Check if outlet is active
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE &&
            $this->validity === self::VALIDITY_ACTIVE;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($outlet) {
            if (is_null($outlet->status)) {
                $outlet->status = self::STATUS_ACTIVE;
            }
            if (is_null($outlet->validity)) {
                $outlet->validity = self::VALIDITY_ACTIVE;
            }
            if (is_null($outlet->entrydate)) {
                $outlet->entrydate = now()->format('Y-m-d');
            }
        });
    }
}
