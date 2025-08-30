<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unitl extends Model
{
    protected $fillable = [
        'unit_name',
        'status',

    ];
    use HasFactory;
}
