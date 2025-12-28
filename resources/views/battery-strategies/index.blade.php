@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Battery Strategies</h1>
        <a href="{{ route('battery-strategies.create') }}" class="btn btn-primary">Create Battery Strategy</a>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Sell Start Time</th>
                    <th>Sell End Time</th>
                    <th>Buy Start Time</th>
                    <th>Buy End Time</th>
                    <th>SOC Lower Bound</th>
                    <th>SOC Upper Bound</th>
                    <th>Strategy Group</th>
                    <th>Is Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batteryStrategies as $batteryStrategy)
                    <tr>
                        <td>{{ $batteryStrategy->name }}</td>
                        <td>{{ $batteryStrategy->description }}</td>
                        <td>{{ $batteryStrategy->sell_start_time }}</td>
                        <td>{{ $batteryStrategy->sell_end_time }}</td>
                        <td>{{ $batteryStrategy->buy_start_time }}</td>
                        <td>{{ $batteryStrategy->buy_end_time }}</td>
                        <td>{{ $batteryStrategy->soc_lower_bound }}</td>
                        <td>{{ $batteryStrategy->soc_upper_bound }}</td>
                        <td>{{ $batteryStrategy->strategy_group }}</td>
                        <td>{{ $batteryStrategy->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('battery-strategies.show', $batteryStrategy->id) }}" class="btn btn-secondary">View</a>
                            <a href="{{ route('battery-strategies.edit', $batteryStrategy->id) }}" class="btn btn-primary">Edit</a>
                            <form action="{{ route('battery-strategies.destroy', $batteryStrategy->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection