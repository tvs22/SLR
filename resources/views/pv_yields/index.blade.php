@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>PV Yields</h1>
        <a href="{{ route('pv-yields.create') }}" class="btn btn-primary">Add PV Yield</a>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Hour</th>
                    <th>KWH</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pvYields as $pvYield)
                    <tr>
                        <td>{{ $pvYield->date }}</td>
                        <td>{{ $pvYield->hour }}</td>
                        <td>{{ $pvYield->kwh }}</td>
                        <td>
                            <a href="{{ route('pv-yields.show', $pvYield) }}" class="btn btn-info">View</a>
                            <a href="{{ route('pv-yields.edit', $pvYield) }}" class="btn btn-primary">Edit</a>
                            <form action="{{ route('pv-yields.destroy', $pvYield) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
