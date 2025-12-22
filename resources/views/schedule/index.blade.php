@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Schedule Management') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p>
                        @if ($isDue)
                            <strong>A schedule is currently running.</strong>
                        @else
                            No schedules are currently running.
                        @endif
                    </p>

                    <p>
                        Next schedule is due to run at: {{ $nextRunDate }}
                    </p>

                    <form action="/schedule/start" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Start</button>
                    </form>
                    <form action="/schedule/stop" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">Stop</button>
                    </form>
                    <form action="/schedule/pause" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">Pause</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
