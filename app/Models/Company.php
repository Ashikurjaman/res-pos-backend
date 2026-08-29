<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'company_name',
        'outlet_name',
        'address',
        'contact_no',
        'email',
        'slogan',
        'pay_type',
        'validity',
    ];

    protected $casts = [
        'validity' => 'integer',
        'pay_type' => 'integer',
    ];

    // ✅ Constants
    const PAY_TYPE_PAID = 1;
    const PAY_TYPE_DUE = 2;
    const VALIDITY_ACTIVE = 1;
    const VALIDITY_INACTIVE = 0;

    // ✅ Scopes
    public function scopeActive($query)
    {
        return $query->where('validity', self::VALIDITY_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('validity', self::VALIDITY_INACTIVE);
    }

    public function scopePaid($query)
    {
        return $query->where('pay_type', self::PAY_TYPE_PAID);
    }

    public function scopeDue($query)
    {
        return $query->where('pay_type', self::PAY_TYPE_DUE);
    }

    // ✅ Accessors
    public function getPayTypeLabelAttribute()
    {
        return $this->pay_type === self::PAY_TYPE_PAID ? 'Paid' : 'Due';
    }

    public function getValidityLabelAttribute()
    {
        return $this->validity === self::VALIDITY_ACTIVE ? 'Active' : 'Inactive';
    }

    public function getFullNameAttribute()
    {
        return $this->company_name . ' (' . $this->outlet_name . ')';
    }

    public function getFullAddressAttribute()
    {
        return $this->address . ' (Contact: ' . $this->contact_no . ')';
    }

    // ✅ Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (is_null($company->validity)) {
                $company->validity = self::VALIDITY_ACTIVE;
            }
            if (is_null($company->pay_type)) {
                $company->pay_type = self::PAY_TYPE_PAID;
            }
        });
    }
}
