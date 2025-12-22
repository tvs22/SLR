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

                    @php
                        $policyToDisplay = null;
                        if ($scheduler && isset($scheduler['result']['groups']) && is_array($scheduler['result']['groups']) && $batterySetting && $batterySetting->discharge_start_time) {
                            $dischargeStartHour = (int) substr($batterySetting->discharge_start_time, 0, 2);
                            foreach ($scheduler['result']['groups'] as $policy) {
                                if (isset($policy['workMode']) && $policy['workMode'] === 'ForceDischarge' && isset($policy['startHour']) && $policy['startHour'] == $dischargeStartHour) {
                                    $policyToDisplay = $policy;
                                    break;
                                }
                            }
                        }
                    @endphp
                    <div class="mt-3">
                        <h5>Fox ESS Status</h5>
                        @if ($policyToDisplay)
                            <p>Force Charge: {{ $policyToDisplay['enable'] ? 'Enabled' : 'Disabled' }}</p>
                            <input type="hidden" id="deviceSN" value="{{ $deviceSN }}">
                            <button id="enableBtn" class="btn btn-success">Enable</button>
                            <button id="disableBtn" class="btn btn-danger">Disable</button>
                        @else
                            <p>Could not retrieve Fox ESS status or find matching policy.</p>
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

            <div class="card mt-4">
                <div class="card-header">{{ __('Battery Transactions') }}</div>
                <div class="card-body" id="battery-transactions">
                    @include('partials.battery-transactions')
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/battery-transactions.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function(){
        const deviceSNElement = document.getElementById('deviceSN');

        if (deviceSNElement) {
            const deviceSN = deviceSNElement.value;
            const enableBtn = document.getElementById('enableBtn');
            const disableBtn = document.getElementById('disableBtn');

            if (enableBtn) {
                enableBtn.addEventListener('click', () => {
                    toggleScheduler(true);
                });
            }

            if (disableBtn) {
                disableBtn.addEventListener('click', () => {
                    toggleScheduler(false);
                });
            }

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
        }
    });
</script>
@endpush
@endsection
