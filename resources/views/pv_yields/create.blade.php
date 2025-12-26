@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Add PV Yield</h1>
        <form action="{{ route('pv-yields.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control">
            </div>
            <div class="form-group">
                <label for="hour">Hour</label>
                <input type="number" name="hour" id="hour" class="form-control">
            </div>
            <div class="form-group">
                <label for="kwh">KWH</label>
                <input type="text" name="kwh" id="kwh" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </div>
@endsection
