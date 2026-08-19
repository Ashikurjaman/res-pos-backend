<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unitls'; // ✅ Correct table name: unitls

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unit_name',
        'status',
        'validity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'validity' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the products for the unit.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'unit');
    }

    /**
     * Scope a query to only include active units.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1')->where('validity', 1);
    }

    /**
     * Scope a query to only include valid units.
     */
    public function scopeValid($query)
    {
        return $query->where('validity', 1);
    }

    /**
     * Check if unit is active.
     */
    public function isActive()
    {
        return $this->status == '1' && $this->validity;
    }

    /**
     * Check if unit is valid.
     */
    public function isValid()
    {
        return $this->validity == 1;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->status == '1' ? 'Active' : 'Inactive';
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeAttribute()
    {
        return $this->status == '1' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unit) {
            if (is_null($unit->validity)) {
                $unit->validity = 1;
            }
            if (is_null($unit->status)) {
                $unit->status = '1';
            }
        });
    }
}