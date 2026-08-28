<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadOfficeStore extends Model
{
    use HasFactory;

    protected $table = 'head_office_store';

    protected $fillable = [
        'product_id',
        'entrydate',
        'balanceinhand',
        'stockbalancebefore',
        'stockbalanceafter',
        'status',
        'opening_balance',
        'validity',
    ];

    protected $casts = [
        'entrydate' => 'date',
        'balanceinhand' => 'decimal:3',
        'stockbalancebefore' => 'decimal:3',
        'stockbalanceafter' => 'decimal:3',
        'opening_balance' => 'decimal:3',
        'status' => 'integer',
        'validity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
