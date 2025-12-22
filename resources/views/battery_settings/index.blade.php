@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Battery Settings</h1>
        <a href="{{ route('battery-settings.create') }}" class="btn btn-primary">Add New Setting</a>
        <table class="table mt-3">
            <thead>
                <tr>
                    <th>Target Price</th>
                    <th>Forced Discharge</th>
                    <th>Discharge Start Time</th>
                    <th>Forced Charge</th>
                    <th>Charge Start Time</th>
                    <th>Battery Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($settings as $setting)
                    <tr>
                        <td>{{ $setting->target_price_cents }}</td>
                        <td>{{ $setting->forced_discharge }}</td>
                        <td>{{ $setting->discharge_start_time }}</td>
                        <td>{{ $setting->forced_charge }}</td>
                        <td>{{ $setting->charge_start_time }}</td>
                        <td>{{ $setting->battery_level_percent }}</td>
                        <td>
                            <a href="{{ route('battery-settings.edit', $setting->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('battery-settings.destroy', $setting->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
