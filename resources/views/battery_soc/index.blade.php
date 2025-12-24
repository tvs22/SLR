@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Battery SOC Management</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-success my-2" href="{{ route('battery_soc.create') }}"> Create New Battery SOC</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <table class="table table-bordered">
        <tr>
            <th>Hour</th>
            <th>SOC</th>
            <th>Type</th>
            <th width="280px">Action</th>
        </tr>
        @foreach ($socData as $soc)
        <tr>
            <td>{{ $soc->hour }}</td>
            <td>{{ $soc->soc }}</td>
            <td>{{ $soc->type }}</td>
            <td>
                <form action="{{ route('battery_soc.destroy',$soc->id) }}" method="POST">
                    <a class="btn btn-info" href="{{ route('battery_soc.show',$soc->id) }}">Show</a>
                    <a class="btn btn-primary" href="{{ route('battery_soc.edit',$soc->id) }}">Edit</a>
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
