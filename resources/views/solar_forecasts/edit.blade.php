@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Edit Solar Forecast</h2>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('solar-forecasts.update',$solarForecast->id) }}" method="POST">
        @csrf
        @method('PUT')

         <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Date:</strong>
                    <input type="date" name="date" value="{{ $solarForecast->date }}" class="form-control">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Hour:</strong>
                    <input type="number" name="hour" value="{{ $solarForecast->hour }}" class="form-control" placeholder="Hour">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Kwh:</strong>
                    <input type="text" name="kwh" value="{{ $solarForecast->kwh }}" class="form-control" placeholder="Kwh">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12 d-flex justify-content-end" style="padding-top: 15px; padding-bottom: 15px;">
              <a class="btn btn-secondary" href="{{ route('solar-forecasts.index') }}" style="margin-right: 10px;"> Cancel</a>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>
@endsection
