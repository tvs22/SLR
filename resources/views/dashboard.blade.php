@extends('layouts.app')

@php
function getPriceClass($price) {
    if ($price === null) return '';
    if ($price > 0) return 'text-success';
    if ($price < 0) return 'text-danger';
    return 'text-muted';
}
@endphp

@section('content')
<div class="container">
    <h1 class="mb-4">Battery Dashboard</h1>

    {{-- Key Metrics Grid --}}
    <div class="row">
        {{-- Prices Card --}}
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Current Prices</h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Electricity price:</strong> <span id="electricity-price" class="{{ getPriceClass(isset($prices['electricityPrice']) ? $prices['electricityPrice'] : null) }}">{{ isset($prices['electricityPrice']) ? number_format($prices['electricityPrice'], 2) . ' c/kWh' : 'n/a' }}</span></p>
                    <p class="card-text"><strong>Solar Feed-in Tariff:</strong> <span id="solar-ftt" class="{{ getPriceClass(isset($prices['solarPrice']) ? $prices['solarPrice'] : null) }}">{{ isset($prices['solarPrice']) ? number_format($prices['solarPrice'], 2) . ' c/kWh' : 'n/a' }}</span></p>
                </div>
            </div>
        </div>

        {{-- Battery Settings Card --}}
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Live Battery Settings</h5>
                </div>
                <div class="card-body">
                    @if ($battery)
                        <p class="card-text"><strong>Target price:</strong> <span id="target-price">{{ number_format($battery->target_price_cents, 2) }}</span> cents</p>
                        <p class="card-text"><strong>Forced Discharge:</strong> <span id="forced-discharge">{{ $battery->forced_discharge ? 'Yes' : 'No' }}</span></p>
                    @else
                        <p class="card-text">No battery settings found.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Last Updated Card --}}
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Last Updated</h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><span id="last-updated">{{ $last_updated ? $last_updated->diffForHumans() : 'n/a' }}</span></p>
                    <p class="card-text text-muted small">Next update in: <span id="next-update-countdown"></span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Price (cents)</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-body">
                                @forelse ($transactions as $t)
                                    <tr>
                                        <td>{{ $t->datetime }}</td>
                                        <td>{{ ucfirst($t->action) }}</td>
                                        <td class="{{ getPriceClass($t->price_cents) }}">{{ number_format($t->price_cents, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No recent transactions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const electricityPriceEl = document.getElementById('electricity-price');
        const solarFttEl = document.getElementById('solar-ftt');
        const targetPriceEl = document.getElementById('target-price');
        const forcedDischargeEl = document.getElementById('forced-discharge');
        const transactionsBodyEl = document.getElementById('transactions-body');
        const lastUpdatedEl = document.getElementById('last-updated');
        const nextUpdateCountdownEl = document.getElementById('next-update-countdown');

        const POLLING_INTERVAL = 300000; // 5 minutes
        let timeRemaining = POLLING_INTERVAL / 1000;

        function getPriceClass(price) {
            if (price === null || price === undefined) return '';
            if (price > 0) return 'text-success';
            if (price < 0) return 'text-danger';
            return 'text-muted';
        }

        function timeSince(date) {
            let seconds = Math.floor((new Date() - date) / 1000);
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            return Math.floor(seconds) + " seconds ago";
        }

        function updateDashboard() {
            fetch('{{ route("dashboard.data") }}')
                .then(response => response.json())
                .then(data => {
                    // Update Prices
                    if (data.prices) {
                        electricityPriceEl.textContent = data.prices.electricityPrice !== null ? parseFloat(data.prices.electricityPrice).toFixed(2) + ' c/kWh' : 'n/a';
                        electricityPriceEl.className = getPriceClass(data.prices.electricityPrice);
                        solarFttEl.textContent = data.prices.solarPrice !== null ? parseFloat(data.prices.solarPrice).toFixed(2) + ' c/kWh' : 'n/a';
                        solarFttEl.className = getPriceClass(data.prices.solarPrice);
                    } else {
                        electricityPriceEl.textContent = 'n/a';
                        electricityPriceEl.className = '';
                        solarFttEl.textContent = 'n/a';
                        solarFttEl.className = '';
                    }

                    // Update Battery Settings
                    if (data.battery) {
                        targetPriceEl.textContent = parseFloat(data.battery.target_price_cents).toFixed(2) + ' cents';
                        forcedDischargeEl.textContent = data.battery.forced_discharge ? 'Yes' : 'No';
                    }

                    // Update Last Updated
                    if (data.last_updated) {
                        lastUpdatedEl.textContent = timeSince(new Date(data.last_updated));
                    } else {
                        lastUpdatedEl.textContent = 'n/a';
                    }

                    // Update Transactions
                    transactionsBodyEl.innerHTML = ''; // Clear existing transactions
                    if (data.transactions && data.transactions.length > 0) {
                        data.transactions.forEach(t => {
                            const row = `<tr>
                                <td>${t.datetime}</td>
                                <td>${t.action.charAt(0).toUpperCase() + t.action.slice(1)}</td>
                                <td class="${getPriceClass(t.price_cents)}">${parseFloat(t.price_cents).toFixed(2)}</td>
                            </tr>`;
                            transactionsBodyEl.innerHTML += row;
                        });
                    } else {
                        const row = `<tr><td colspan="3" class="text-center">No recent transactions found.</td></tr>`;
                        transactionsBodyEl.innerHTML = row;
                    }

                    // Reset countdown
                    timeRemaining = POLLING_INTERVAL / 1000;
                })
                .catch(error => console.error('Error fetching dashboard data:', error));
        }

        function updateCountdown() {
            timeRemaining--;
            if (timeRemaining < 0) timeRemaining = 0;
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            nextUpdateCountdownEl.textContent = `${minutes}m ${seconds}s`;
        }

        // Fetch data every 5 minutes
        setInterval(updateDashboard, POLLING_INTERVAL);

        // Update countdown every second
        setInterval(updateCountdown, 1000);

        // Initial call to populate data
        updateDashboard();
        updateCountdown();
    });
</script>
@endpush
