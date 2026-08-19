<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_models'; // ✅ Changed from 'categories' to 'category_models'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_name',
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
     * Get the products for the category.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Get the sale details for the category.
     */
    public function saleDetails()
    {
        return $this->hasMany(Saledetails::class, 'category_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '1')->where('validity', 1);
    }

    /**
     * Scope a query to only include valid categories.
     */
    public function scopeValid($query)
    {
        return $query->where('validity', 1);
    }

    /**
     * Check if category is active.
     */
    public function isActive()
    {
        return $this->status == '1' && $this->validity;
    }

    /**
     * Check if category is valid.
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
     * Get the formatted created at date.
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, h:i A') : null;
    }

    /**
     * Get the formatted updated at date.
     */
    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d M Y, h:i A') : null;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (is_null($category->validity)) {
                $category->validity = 1;
            }
            if (is_null($category->status)) {
                $category->status = '1';
            }
        });
    }
}