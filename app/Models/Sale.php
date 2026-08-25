<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'entryDate',
        'invoiceNo',
        'discount',
        'sd',
        'vat',
        'total',
        'received',
        'change',
        'paymentMode',
        'table_id',
        'user',
        'status',
        'validity',
    ];

    protected $attributes = [
        'total' => 0,
        'discount' => 0,
        'sd' => 0,
        'vat' => 0,
        'received' => 0,
        'change' => 0,
        'status' => 'active',
        'validity' => 1,
        'paymentMode' => 'Cash', // ✅ Add default payment mode
    ];

    protected $casts = [
        'validity' => 'boolean',
        'entryDate' => 'date',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'sd' => 'decimal:2',
        'vat' => 'decimal:2',
        'received' => 'decimal:2',
        'change' => 'decimal:2',
        'table_id' => 'integer',
    ];

    use HasFactory;

    public function details()
    {
        return $this->hasMany(Saledetails::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    // ✅ Add scope for completed sales
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')->where('validity', 1);
    }

    // ✅ Add scope for active sales
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('validity', 1);
    }

    // ✅ Add scope for valid sales
    public function scopeValid($query)
    {
        return $query->where('validity', 1);
    }
}
