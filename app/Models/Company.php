<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    // If your table name is different, specify it
    // protected $table = 'company_models';

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
        'validity' => 'boolean',
        'pay_type' => 'integer',
    ];
}
