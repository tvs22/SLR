<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BatterySetting extends Model
{
    protected $fillable = [
        'target_price_cents',
        'forced_discharge',
        'discharge_start_time',
        'forced_charge',
        'charge_start_time',
        'battery_level_percent',
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
