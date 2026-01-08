@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb d-flex justify-content-between align-items-center">
            <div class="pull-left">
                <h2>Solar Forecast Management</h2>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a class="btn btn-success" href="{{ route('solar-forecasts.create') }}"> Create New Solar Forecast</a>
                <form action="{{ route('solar-forecasts.get-forecasts') }}" method="GET" class="mb-0">
                    <div class="input-group">
                        <input type="number" class="form-control" name="kwp" placeholder="KWP" value="10" step="any">
                        <button class="btn btn-info" type="submit">Get Latest Forecast</button>
                    </div>
                </form>
                <a class="btn btn-danger" href="{{ route('solar-forecasts.delete-all') }}" onclick="return confirm('Are you sure you want to delete all solar forecasts?')">Delete All</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-2">
            <p>{{ $message }}</p>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger mt-2">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered mt-2">
        <tr>
            <th>Date</th>
            <th>Hour</th>
            <th>Kwh</th>
            <th width="280px">Action</th>
        </tr>
        @foreach ($solarForecasts as $forecast)
        <tr>
            <td>{{ $forecast->date }}</td>
            <td>{{ $forecast->hour }}</td>
            <td>{{ $forecast->kwh }}</td>
            <td>
                <form action="{{ route('solar-forecasts.destroy',$forecast->id) }}" method="POST">
                    <a class="btn btn-info" href="{{ route('solar-forecasts.show',$forecast->id) }}">Show</a>
                    <a class="btn btn-primary" href="{{ route('solar-forecasts.edit',$forecast->id) }}">Edit</a>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
