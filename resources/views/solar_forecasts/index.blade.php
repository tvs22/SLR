@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb d-flex justify-content-between align-items-center">
            <div class="pull-left">
                <h2>Solar Forecast Management</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-success my-2" href="{{ route('solar-forecasts.create') }}" style="padding: 10px;"> Create New Solar Forecast</a>
                <a class="btn btn-info my-2" href="{{ route('solar-forecasts.get-forecasts') }}" style="padding: 10px;">Get Latest Forecast</a>
                <a class="btn btn-danger my-2" href="{{ route('solar-forecasts.delete-all') }}" onclick="return confirm('Are you sure you want to delete all solar forecasts?')" style="padding: 10px;">Delete All</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered">
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
