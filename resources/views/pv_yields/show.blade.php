@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>PV Yield Details</h1>
        <table class="table">
            <tbody>
                <tr>
                    <th>Date</th>
                    <td>{{ $pvYield->date }}</td>
                </tr>
                <tr>
                    <th>Hour</th>
                    <td>{{ $pvYield->hour }}</td>
                </tr>
                <tr>
                    <th>KWH</th>
                    <td>{{ $pvYield->kwh }}</td>
                </tr>
            </tbody>
        </table>
        <a href="{{ route('pv-yields.index') }}" class="btn btn-primary">Back to List</a>
    </div>
@endsection
