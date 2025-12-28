@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Battery Setting</h1>
        <form action="{{ route('battery-settings.update', $batterySetting->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="target_price_cents">Target Price (Cents)</label>
                <input type="number" name="target_price_cents" id="target_price_cents" class="form-control" value="{{ $batterySetting->target_price_cents }}" required>
            </div>
            <div class="form-group">
                <label for="longterm_target_price_cents">Long-Term Target Price (Cents)</label>
                <input type="number" name="longterm_target_price_cents" id="longterm_target_price_cents" class="form-control" value="{{ $batterySetting->longterm_target_price_cents }}" required>
            </div>
            <div class="form-group">
                <label for="forced_discharge">Forced Discharge</label>
                <select name="forced_discharge" id="forced_discharge" class="form-control" required>
                    <option value="1" {{ $batterySetting->forced_discharge ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ !$batterySetting->forced_discharge ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="form-group">
                <label for="target_electric_price_cents">Target Electric Price (Cents)</label>
                <input type="number" name="target_electric_price_cents" id="target_electric_price_cents" class="form-control" value="{{ $batterySetting->target_electric_price_cents }}" required>
            </div>
            <div class="form-group">
                <label for="longterm_target_electric_price_cents">Long-Term Target Electric Price (Cents)</label>
                <input type="number" name="longterm_target_electric_price_cents" id="longterm_target_electric_price_cents" class="form-control" value="{{ $batterySetting->longterm_target_electric_price_cents }}" required>
            </div>
            <div class="form-group">
                <label for="forced_charge">Forced Charge</label>
                <select name="forced_charge" id="forced_charge" class="form-control" required>
                    <option value="1" {{ $batterySetting->forced_charge ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ !$batterySetting->forced_charge ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="form-group">
                <label for="battery_level_percent">Battery Level (%)</label>
                <input type="number" name="battery_level_percent" id="battery_level_percent" class="form-control" step="0.01" value="{{ $batterySetting->battery_level_percent }}" required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="prioritize_charging" {{ $batterySetting->status == 'prioritize_charging' ? 'selected' : '' }}>Prioritize Charging</option>
                    <option value="prioritize_selling" {{ $batterySetting->status == 'prioritize_selling' ? 'selected' : '' }}>Prioritize Selling</option>
                    <option value="self_sufficient" {{ $batterySetting->status == 'self_sufficient' ? 'selected' : '' }}>Self Sufficient</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Update Setting</button>
        </form>
    </div>
@endsection
