@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Add New Battery Setting</h1>
        <form action="{{ route('battery-settings.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="target_price_cents">Target Price (Cents)</label>
                <input type="number" name="target_price_cents" id="target_price_cents" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="forced_discharge">Forced Discharge</label>
                <select name="forced_discharge" id="forced_discharge" class="form-control" required>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="form-group">
                <label for="discharge_start_time">Discharge Start Time</label>
                <input type="time" name="discharge_start_time" id="discharge_start_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="target_electric_price_cents">Target Electric Price (Cents)</label>
                <input type="number" name="target_electric_price_cents" id="target_electric_price_cents" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="forced_charge">Forced Charge</label>
                <select name="forced_charge" id="forced_charge" class="form-control" required>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="form-group">
                <label for="charge_start_time">Charge Start Time</label>
                <input type="time" name="charge_start_time" id="charge_start_time" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="battery_level_percent">Battery Level (%)</label>
                <input type="number" name="battery_level_percent" id="battery_level_percent" class="form-control" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Setting</button>
        </form>
    </div>
@endsection
