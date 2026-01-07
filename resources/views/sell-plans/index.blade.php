@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Sell Plans</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Revenue</th>
                <th>KWH</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sellPlans as $plan)
            <tr>
                <td>{{ $plan->time }}</td>
                <td>{{ $plan->revenue }}</td>
                <td>{{ $plan->kwh }}</td>
                <td>{{ $plan->price }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
