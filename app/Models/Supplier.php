<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliersetup';

    protected $fillable = [
        'entrydate',
        'supplier_name',
        'address',
        'contact_no',
        'username',
        'bin_nid',
        'ope_balance',
        'adv_balance',
        'due_balance',
        'validity',
    ];

    protected $casts = [
        'entrydate' => 'date',
        'ope_balance' => 'decimal:2',
        'adv_balance' => 'decimal:2',
        'due_balance' => 'decimal:2',
        'validity' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('validity', 1);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->supplier_name . ' (' . $this->username . ')';
    }

    public function getBalanceAttribute()
    {
        return $this->due_balance - $this->adv_balance;
    }

    // Relationships
    public function ledgers()
    {
        return $this->hasMany(SupplierLedger::class, 'supplier_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_product', 'supplier_id', 'product_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (is_null($supplier->validity)) {
                $supplier->validity = 1;
            }
            if (is_null($supplier->entrydate)) {
                $supplier->entrydate = now()->format('Y-m-d');
            }
        });
    }
}
