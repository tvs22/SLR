<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatterySetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'target_price_cents',
        'longterm_target_price_cents',
        'forced_discharge',
        'target_electric_price_cents',
        'longterm_target_electric_price_cents',
        'forced_charge',
        'battery_level_percent',
        'status',
    ];

    protected $casts = [
        'forced_discharge' => 'boolean',
        'forced_charge'=>'boolean'
    ];

    public function batteryTransactions()
    {
        return $this->hasMany(BatteryTransaction::class);
    }
}
