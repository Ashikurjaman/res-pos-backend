<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierLedger extends Model
{
    use HasFactory;

    protected $table = 'supplier_ledger';

    protected $fillable = [
        'entry_date',
        'supplier_id',
        'table_name',
        'unique_id',
        'description',
        'debit_amt',
        'credit_amt',
        'type',
        'closing_balance',
        'user_id',
        'validity',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit_amt' => 'decimal:3',
        'credit_amt' => 'decimal:3',
        'closing_balance' => 'decimal:3',
        'validity' => 'integer',
    ];

    const TYPE_DEBIT = 1;
    const TYPE_CREDIT = 2;

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
