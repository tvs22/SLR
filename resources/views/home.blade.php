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
                        <p>Force Charge: 

                        </p>
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
    // Global var to hold the next refresh time
    var nextRefreshTime;

    function updateClock() {
        var now = new Date();
        var hours = now.getHours();
        var minutes = now.getMinutes();
        var seconds = now.getSeconds();
        
        // Add leading zeros if needed
        hours = (hours < 10) ? "0" + hours : hours;
        minutes = (minutes < 10) ? "0" + minutes : minutes;
        seconds = (seconds < 10) ? "0" + seconds : seconds;

        var timeString = hours + ":" + minutes + ":" + seconds;
        document.getElementById('clock').innerHTML = timeString;
    }

    function updateCountdown() {
        var now = new Date();
        var remaining = nextRefreshTime.getTime() - now.getTime();
        
        if (remaining < 0) {
            document.getElementById('countdown').innerHTML = "Refreshing...";
            return;
        }

        var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((remaining % (1000 * 60)) / 1000);

        // Add leading zeros
        minutes = (minutes < 10) ? "0" + minutes : minutes;
        seconds = (seconds < 10) ? "0" + seconds : seconds;

        document.getElementById('countdown').innerHTML = "Refresh in: " + minutes + ":" + seconds;
    }


    function scheduleNextRefresh() {
        var now = new Date();
        var then = new Date(now.getTime());

        then.setSeconds(41, 0);

        var currentMinutes = now.getMinutes();
        var nextRefreshMinuteBlock = Math.floor(currentMinutes / 5) * 5;
        
        var nextMinutes;

        if (currentMinutes > nextRefreshMinuteBlock || 
           (currentMinutes === nextRefreshMinuteBlock && now.getSeconds() >= 45)) {
            nextMinutes = nextRefreshMinuteBlock + 5;
        } else {
            nextMinutes = nextRefreshMinuteBlock;
        }

        if (nextMinutes >= 60) {
            then.setHours(then.getHours() + 1);
            nextMinutes = 0;
        }
        
        then.setMinutes(nextMinutes);
        
        nextRefreshTime = then; 
        
        var timeout = (nextRefreshTime.getTime() - now.getTime());
        setTimeout(function() { window.location.reload(true); }, timeout);
    }

    document.addEventListener("DOMContentLoaded", function(){
        // Update the clock every second
        setInterval(updateClock, 1000);
        updateClock(); // initial call

        // Schedule the first refresh and start the countdown timer
        scheduleNextRefresh();
        setInterval(updateCountdown, 1000);
        updateCountdown(); // initial call
    });
</script>
@endpush
@endsection
