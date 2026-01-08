@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Sell Plans</h1>
    <form action="{{ route('sell-plans.destroy') }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('sell-plans.export') }}" class="btn btn-success" style="margin-right: 5px;">Export to Excel</a>
            <button type="submit" class="btn btn-danger">Delete Selected</button>
        </div>
        @foreach($sellPlans as $created_at => $plans)
            <div class="card mb-3">
                <div class="card-header">
                    <input type="checkbox" name="selected_groups[]" value="{{ $created_at }}">
                    <strong>Created at: {{ $created_at }}</strong>
                </div>
                <div class="card-body">
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
                            @foreach($plans as $plan)
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
            </div>
        @endforeach
    </form>
</div>
@endsection
