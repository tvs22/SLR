@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $batteryStrategy->name }}</h1>
        <p><strong>Description:</strong> {{ $batteryStrategy->description }}</p>
        <p><strong>Sell Start Time:</strong> {{ $batteryStrategy->sell_start_time }}</p>
        <p><strong>Sell End Time:</strong> {{ $batteryStrategy->sell_end_time }}</p>
        <p><strong>Buy Start Time:</strong> {{ $batteryStrategy->buy_start_time }}</p>
        <p><strong>Buy End Time:</strong> {{ $batteryStrategy->buy_end_time }}</p>
        <p><strong>SOC Lower Bound:</strong> {{ $batteryStrategy->soc_lower_bound }}</p>
        <p><strong>SOC Upper Bound:</strong> {{ $batteryStrategy->soc_upper_bound }}</p>
        <p><strong>Strategy Group:</strong> {{ $batteryStrategy->strategy_group }}</p>
        <p><strong>Is Active:</strong> {{ $batteryStrategy->is_active ? 'Yes' : 'No' }}</p>
        <a href="{{ route('battery-strategies.index') }}" class="btn btn-secondary">Back to all strategies</a>
    </div>
@endsection