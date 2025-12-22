<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BatteryTransaction extends Model
{
    public function batterySetting()
    {
        return $this->belongsTo(BatterySetting::class);
    }
}
