@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Show Solar Forecast</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Date:</strong>
                {{ $solarForecast->date }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Hour:</strong>
                {{ $solarForecast->hour }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Kwh:</strong>
                {{ $solarForecast->kwh }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 d-flex justify-content-end" style="padding-top: 15px; padding-bottom: 15px;">
            <a class="btn btn-secondary" href="{{ route('solar-forecasts.index') }}"> Back</a>
        </div>
    </div>
</div>
@endsection
