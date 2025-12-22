<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BatteryTransaction extends Model
{
    protected $fillable = [
        'battery_id',
        'datetime',
        'price_cents',
        'action',
    ];

    public function batterySetting()
    {
        return $this->belongsTo(BatterySetting::class, 'battery_id');
    }
}
