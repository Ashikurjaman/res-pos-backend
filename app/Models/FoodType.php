<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodType extends Model
{
    use HasFactory;

    protected $table = 'food_types';

    protected $fillable = [
        'type_name',
        'printer_ip',
        'onlinestatus',
        'validity',
    ];

    protected $casts = [
        'onlinestatus' => 'integer',
        'validity' => 'integer',
    ];

    // Constants
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 0;
    const VALIDITY_ACTIVE = 1;
    const VALIDITY_INACTIVE = 0;

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('validity', self::VALIDITY_ACTIVE);
    }

    public function scopeOnline($query)
    {
        return $query->where('onlinestatus', self::STATUS_ONLINE);
    }

    public function scopeInactive($query)
    {
        return $query->where('validity', self::VALIDITY_INACTIVE);
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class, 'food_type_id');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return $this->onlinestatus == self::STATUS_ONLINE ? 'Online' : 'Offline';
    }

    public function getValidityLabelAttribute()
    {
        return $this->validity == self::VALIDITY_ACTIVE ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->onlinestatus == self::STATUS_ONLINE && $this->validity == self::VALIDITY_ACTIVE) {
            return 'bg-green-100 text-green-800';
        } elseif ($this->onlinestatus == self::STATUS_OFFLINE) {
            return 'bg-yellow-100 text-yellow-800';
        } else {
            return 'bg-red-100 text-red-800';
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($foodType) {
            if (is_null($foodType->validity)) {
                $foodType->validity = self::VALIDITY_ACTIVE;
            }
            if (is_null($foodType->onlinestatus)) {
                $foodType->onlinestatus = self::STATUS_ONLINE;
            }
        });
    }
}
