<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatteryStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sell_start_time',
        'sell_end_time',
        'buy_start_time',
        'buy_end_time',
        'soc_lower_bound',
        'soc_upper_bound',
        'strategy_group',
        'is_active',
    ];
}
