@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb d-flex justify-content-between align-items-center">
            <div class="pull-left">
                <h2>Battery SOC Management</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-success my-2" href="{{ route('battery_soc.create') }}" style="padding: 10px;"> Create New Battery SOC</a>
                <a class="btn btn-info my-2" href="{{ route('battery_soc.get-soc') }}" style="padding: 10px;">Get Current SOC</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <p>{{ $message }}</p>
        </div>
    @endif

    <form action="{{ route('battery_soc.index') }}" method="GET" class="my-3">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="soc_plans" {{ request('type') == 'soc_plans' ? 'selected' : '' }}>SOC Plan</option>
                        <option value="soc_low_plans" {{ request('type') == 'soc_low_plans' ? 'selected' : '' }}>SOC Low Plan</option>
                        <option value="current" {{ request('type') == 'current' ? 'selected' : '' }}>Current</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

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
