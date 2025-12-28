@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Battery Settings</h1>
        <a href="{{ route('battery-settings.create') }}" class="btn btn-primary">Add New Setting</a>
        <table class="table mt-3">
            <thead>
                <tr>
                    <th>Target Price</th>
                    <th>Long-Term Target Price</th>
                    <th>Forced Discharge</th>
                    <th>Target Electric Price</th>
                    <th>Long-Term Target Electric Price</th>
                    <th>Forced Charge</th>
                    <th>Battery Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($settings as $setting)
                    <tr>
                        <td>{{ $setting->target_price_cents }}</td>
                        <td>{{ $setting->longterm_target_price_cents }}</td>
                        <td>{{ $setting->forced_discharge ? 'Yes' : 'No' }}</td>
                        <td>{{ $setting->target_electric_price_cents }}</td>
                        <td>{{ $setting->longterm_target_electric_price_cents }}</td>
                        <td>{{ $setting->forced_charge ? 'Yes' : 'No' }}</td>
                        <td>{{ $setting->battery_level_percent }}</td>
                        <td>{{ $setting->status }}</td>
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
