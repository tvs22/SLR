@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }} 
                    <span id="countdown" class="float-end"></span>
                    <span id="clock" class="float-end me-2"></span>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}

                    <div class="mt-3">
                        <h5>Amber Electric Rates</h5>
                        @if ($electricityPrice !== null)
                            <p>Current Electricity Price: {{ round($electricityPrice) }} c/kWh</p>
                        @else
                            <p>Could not retrieve electricity price. Please check your Amber Electric API key and Site ID in the .env file.</p>
                        @endif
                        @if ($solarPrice !== null)
                            <p>Current Solar Feed-in Price: {{ round($solarPrice) }} c/kWh</p>
                        @else
                            <p>Could not retrieve solar feed-in price. Please check your Amber Electric API key and Site ID in the .env file.</p>
                        @endif
                    </div>

                    <div class="mt-3">
                        <h5>Fox ESS Status</h5>
                        @if ($scheduler && $scheduler['result'] && isset($scheduler['result']['groups'][0]['enable']))
                            <p>Force Charge: {{ $scheduler['result']['groups'][0]['enable'] ? 'Enabled' : 'Disabled' }}</p>
                            <input type="hidden" id="deviceSN" value="{{ $deviceSN }}">
                            <button id="enableBtn" class="btn btn-success">Enable</button>
                            <button id="disableBtn" class="btn btn-danger">Disable</button>
                        @else
                            <p>Could not retrieve Fox ESS status.</p>
                        @endif
                    </div>

                    <form action="/submit" method="POST" class="mt-3">
                        @csrf
                        <div class="form-group">
                            <label for="battery_level">Battery Level</label>
                            <input type="text" class="form-control" id="battery_level" name="battery_level">
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function(){
        const deviceSN = document.getElementById('deviceSN').value;

        document.getElementById('enableBtn').addEventListener('click', () => {
            toggleScheduler(true);
        });

        document.getElementById('disableBtn').addEventListener('click', () => {
            toggleScheduler(false);
        });

        function toggleScheduler(enable) {
            fetch('/scheduler/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deviceSN: deviceSN,
                    enable: enable
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        }
    });
</script>
@endpush
@endsection
