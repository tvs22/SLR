<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'time',
        'revenue',
        'kwh',
        'price',
    ];
}
