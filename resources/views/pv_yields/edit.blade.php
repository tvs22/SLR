@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit PV Yield</h1>
        <form action="{{ route('pv-yields.update', $pvYield) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $pvYield->date }}">
            </div>
            <div class="form-group">
                <label for="hour">Hour</label>
                <input type="number" name="hour" id="hour" class="form-control" value="{{ $pvYield->hour }}">
            </div>
            <div class="form-group">
                <label for="kwh">KWH</label>
                <input type="text" name="kwh" id="kwh" class="form-control" value="{{ $pvYield->kwh }}">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
@endsection
