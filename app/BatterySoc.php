<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatterySoc extends Model
{
    use HasFactory;

    protected $table = 'battery_soc';

    protected $fillable = ['hour', 'soc', 'type'];
}
