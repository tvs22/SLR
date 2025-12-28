@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Battery Strategy</h1>
        <form action="{{ route('battery-strategies.update', $batteryStrategy->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $batteryStrategy->name }}">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control">{{ $batteryStrategy->description }}</textarea>
            </div>
            <div class="form-group">
                <label for="sell_start_time">Sell Start Time</label>
                <input type="time" name="sell_start_time" id="sell_start_time" class="form-control" value="{{ $batteryStrategy->sell_start_time }}">
            </div>
            <div class="form-group">
                <label for="sell_end_time">Sell End Time</label>
                <input type="time" name="sell_end_time" id="sell_end_time" class="form-control" value="{{ $batteryStrategy->sell_end_time }}">
            </div>
            <div class="form-group">
                <label for="buy_start_time">Buy Start Time</label>
                <input type="time" name="buy_start_time" id="buy_start_time" class="form-control" value="{{ $batteryStrategy->buy_start_time }}">
            </div>
            <div class="form-group">
                <label for="buy_end_time">Buy End Time</label>
                <input type="time" name="buy_end_time" id="buy_end_time" class="form-control" value="{{ $batteryStrategy->buy_end_time }}">
            </div>
            <div class="form-group">
                <label for="soc_lower_bound">SOC Lower Bound</label>
                <input type="number" name="soc_lower_bound" id="soc_lower_bound" class="form-control" value="{{ $batteryStrategy->soc_lower_bound }}">
            </div>
            <div class="form-group">
                <label for="soc_upper_bound">SOC Upper Bound</label>
                <input type="number" name="soc_upper_bound" id="soc_upper_bound" class="form-control" value="{{ $batteryStrategy->soc_upper_bound }}">
            </div>
            <div class="form-group">
                <label for="strategy_group">Strategy Group</label>
                <input type="text" name="strategy_group" id="strategy_group" class="form-control" value="{{ $batteryStrategy->strategy_group }}">
            </div>
            <div class="form-group">
                <label for="is_active">Is Active</label>
                <select name="is_active" id="is_active" class="form-control">
                    <option value="1" {{ $batteryStrategy->is_active ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ !$batteryStrategy->is_active ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
@endsection