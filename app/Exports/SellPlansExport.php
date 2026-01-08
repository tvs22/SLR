<?php

namespace App\Exports;

use App\Models\SellPlan;
use Maatwebsite\Excel\Concerns\FromCollection;

class SellPlansExport implements FromCollection
{
    public function collection()
    {
        return SellPlan::all();
    }
}
