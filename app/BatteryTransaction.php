<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BatteryTransaction extends Model
{
    protected $fillable = [
        'battery_setting_id',
        'datetime',
        'price_cents',
        'action',
    ];

    protected $casts = [
        'datetime' => 'datetime',
    ];

    public function batterySetting()
    {
        return $this->belongsTo(BatterySetting::class);
    }
}
