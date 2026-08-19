<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $table = 'tables';

    protected $fillable = [
        'table_number',
        'table_name',
        'status',
        'validity'
    ];

    protected $casts = [
        'validity' => 'boolean',
    ];

    // Relationships
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function activeSales()
    {
        return $this->hasMany(Sale::class)->where('status', 'active')->where('validity', 1);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('validity', 1);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied')->where('validity', 1);
    }

    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved')->where('validity', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }

    // Helper methods
    public function isAvailable()
    {
        return $this->status === 'available' && $this->validity;
    }

    public function isOccupied()
    {
        return $this->status === 'occupied' && $this->validity;
    }

    public function isReserved()
    {
        return $this->status === 'reserved' && $this->validity;
    }

    public function occupy()
    {
        $this->status = 'occupied';
        return $this->save();
    }

    public function release()
    {
        $this->status = 'available';
        return $this->save();
    }

    public function reserve()
    {
        $this->status = 'reserved';
        return $this->save();
    }

    public function getStatusBadgeColorAttribute()
    {
        return match ($this->status) {
            'available' => 'bg-green-500',
            'occupied' => 'bg-red-500',
            'reserved' => 'bg-yellow-500',
            default => 'bg-gray-500',
        };
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }
}