@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Edit Battery SOC</h2>
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

    <form action="{{ route('battery_soc.update',$batterySoc->id) }}" method="POST">
        @csrf
        @method('PUT')

         <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Hour:</strong>
                    <input type="number" name="hour" value="{{ $batterySoc->hour }}" class="form-control" placeholder="Hour">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>SOC:</strong>
                    <input type="number" name="soc" value="{{ $batterySoc->soc }}" class="form-control" placeholder="SOC">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Type:</strong>
                    <select name="type" class="form-control">
                        <option value="soc_plans" @if($batterySoc->type == 'soc_plans') selected @endif>SOC Plan</option>
                        <option value="soc_low_plans" @if($batterySoc->type == 'soc_low_plans') selected @endif>SOC Low Plan</option>
                        <option value="current" @if($batterySoc->type == 'current') selected @endif>Current</option>
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12 d-flex justify-content-end" style="padding-top: 15px; padding-bottom: 15px;">
              <a class="btn btn-secondary" href="{{ route('battery_soc.index') }}" style="margin-right: 10px;"> Cancel</a>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>
@endsection
