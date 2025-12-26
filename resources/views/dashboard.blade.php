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

    <div class="row">
        {{-- Prices & Status --}}
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Live Status</h5>
                    <small class="text-muted">Real-time electricity prices and battery status</small>
                </div>
                <div class="card-body">
                    <p><strong>Electricity price:</strong> <span id="electricity-price" class="{{ getPriceClass($prices['electricityPrice'] ?? null) }}">{{ isset($prices['electricityPrice']) ? number_format($prices['electricityPrice'], 2) . ' c/kWh' : 'n/a' }}</span></p>
                    <p><strong>Solar Feed-in Tariff:</strong> <span id="solar-ftt" class="{{ getPriceClass($prices['solarPrice'] ?? null) }}">{{ isset($prices['solarPrice']) ? number_format($prices['solarPrice'], 2) . ' c/kWh' : 'n/a' }}</span></p>
                    <hr>
                    <p><strong>Forced Discharge:</strong> <span id="forced-discharge">{{ ($battery->forced_discharge ?? false) ? 'Yes' : 'No' }}</span></p>
                    <p><strong>Forced Charge:</strong> <span id="forced-charge">{{ ($battery->forced_charge ?? false) ? 'Yes' : 'No' }}</span></p>
                    <hr>
                    <h6>Battery Settings</h6>
                    <p><strong>Target price:</strong> <span id="target-price">{{ number_format($battery->target_price_cents ?? 0, 2) }} Cents</span></p>
                    <p><strong>Target Electric Price:</strong> <span id="target-electric-price">{{ number_format($battery->target_electric_price_cents ?? 0, 2) }} Cents</span></p>
                </div>
                <div class="card-footer">
                    <small class="text-muted">Last updated: <span id="last-updated">{{ $last_updated ? $last_updated->diffForHumans() : 'n/a' }}</span></small><br>
                    <small class="text-muted">Next update in: <span id="next-update-countdown"></span></small>
                </div>
            </div>
        </div>

        {{-- SOC & Solar --}}
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Battery & Solar</h5>
                    <small class="text-muted">Current state of charge and solar generation forecast</small>
                </div>
                <div class="card-body">
                    <p><strong>Current SOC:</strong> <span id="soc">{{ $soc ? $soc . '%' : 'n/a' }}</span></p>
                    <p><strong>Remaining Solar Generation:</strong> <span id="remaining-solar">{{ number_format($remaining_solar_generation_today, 2) . ' kWh' ?? 'n/a' }}</span></p>
                    <p><strong>Forecast SOC:</strong> <span id="forecast-soc">{{ $forecast_soc . '%' ?? 'n/a' }}</span></p>
                    <hr>
                    <h6>Solar Power</h6>
                    <p><strong>Today's Forecast:</strong> <span id="today-forecast">{{ number_format($todayForecast ?? 0, 2) }} kWh</span></p>
                    <p><strong>Tomorrow's Forecast:</strong> <span id="tomorrow-forecast">{{ number_format($tomorrowForecast ?? 0, 2) }} kWh</span></p>
                </div>
            </div>
        </div>

        {{-- Sell Strategy --}}
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Sell Strategy</h5>
                    <small class="text-muted">Optimal times to sell back to the grid</small>
                </div>
                <div class="card-body" id="sell-strategy-container">
                    @if ($sellStrategy && isset($sellStrategy['error']))
                        <p class="text-danger">{{ $sellStrategy['error'] }}</p>
                    @elseif ($sellStrategy && isset($sellStrategy['message']))
                        <p>{{ $sellStrategy['message'] }}</p>
                    @elseif ($sellStrategy)
                        <p><strong>Total kWh to be sold:</strong> <span id="sell-kwh">{{ number_format($sellStrategy['total_kwh_sold'] ?? 0, 2) }} kWh</span></p>
                        <p><strong>Total Revenue:</strong> <span id="sell-revenue">${{ number_format(($sellStrategy['total_revenue'] ?? 0) / 100, 2) }}</span></p>
                        <p><strong>Highest Sell Price:</strong> <span id="highest-sell-price">{{ number_format($sellStrategy['highest_sell_price'] ?? 0, 2) }} c/kWh</span></p>
                        <p><strong>Lowest Sell Price:</strong> <span id="lowest-sell-price">{{ number_format($sellStrategy['lowest_sell_price'] ?? 0, 2) }} c/kWh</span></p>
                    @else
                        <p>No data available.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Buy Strategy --}}
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5>Buy Strategy</h5>
                    <small class="text-muted">Optimal times to charge from the grid</small>
                </div>
                <div class="card-body" id="buy-strategy-container">
                    @if ($buyStrategy && isset($buyStrategy['error']))
                        <p class="text-danger">{{ $buyStrategy['error'] }}</p>
                    @elseif ($buyStrategy && isset($buyStrategy['message']))
                        <p>{{ $buyStrategy['message'] }}</p>
                    @elseif ($buyStrategy)
                        <p><strong>kWh to buy:</strong> <span id="buy-kwh">{{ number_format($kwh_to_buy ?? 0, 2) }} kWh</span></p>
                        <p><strong>Total Cost:</strong> <span id="buy-cost">${{ number_format(($buyStrategy['total_cost'] ?? 0) / 100, 2) }}</span></p>
                        <p><strong>Total Revenue:</strong> <span id="buy-revenue">${{ number_format(($buyStrategy['total_revenue'] ?? 0) / 100, 2) }}</span></p>
                        <p><strong>Estimated Profit:</strong> <span id="buy-profit">${{ number_format(($buyStrategy['estimated_profit'] ?? 0) / 100, 2) }}</span></p>
                        <p><strong>Highest Buy Price:</strong> <span id="highest-buy-price">{{ number_format($buyStrategy['highest_buy_price'] ?? 0, 2) }} c/kWh</span></p>
                        <p><strong>Lowest Sell Price:</strong> <span id="lowest-sell-price">{{ number_format($buyStrategy['lowest_sell_price'] ?? 0, 2) }} c/kWh</span></p>
                    @else
                        <p>No data available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction Log --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Transactions</h5>
                    <small class="text-muted">The last 10 transactions recorded</small>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Price (c/kWh)</th>
                            </tr>
                        </thead>
                        <tbody id="transaction-log-body">
                            @php
                                $currentDate = null;
                            @endphp
                            @if ($transactions && $transactions->count() > 0)
                                @foreach ($transactions as $transaction)
                                    @if ($currentDate !== $transaction->datetime->format('Y-m-d'))
                                        @php
                                            $currentDate = $transaction->datetime->format('Y-m-d');
                                        @endphp
                                        <tr>
                                            <td colspan="3" class="text-center table-secondary"><strong>{{ $transaction->datetime->format('l, F j, Y') }}</strong></td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td>{{ $transaction->datetime->format('H:i:s') }}</td>
                                        <td>{{ $transaction->action }}</td>
                                        <td>{{ number_format($transaction->price_cents, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center">No transactions found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const POLLING_INTERVAL = 300000; // 5 minutes
        let timeRemaining = POLLING_INTERVAL / 1000;

        function getPriceClass(price) {
            if (price === null || price === undefined) return '';
            if (price > 0) return 'text-success';
            if (price < 0) return 'text-danger';
            return 'text-muted';
        }

        function timeSince(date) {
            if (!date) return 'n/a';
            let seconds = Math.floor((new Date() - new Date(date)) / 1000);
            if (seconds < 5) return 'just now';
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
                    // Prices & Status
                    if (data.prices) {
                        document.getElementById('electricity-price').textContent = data.prices.electricityPrice !== null ? parseFloat(data.prices.electricityPrice).toFixed(2) + ' c/kWh' : 'n/a';
                        document.getElementById('electricity-price').className = getPriceClass(data.prices.electricityPrice);
                        document.getElementById('solar-ftt').textContent = data.prices.solarPrice !== null ? parseFloat(data.prices.solarPrice).toFixed(2) + ' c/kWh' : 'n/a';
                        document.getElementById('solar-ftt').className = getPriceClass(data.prices.solarPrice);
                    }
                    if (data.battery) {
                        document.getElementById('forced-discharge').textContent = data.battery.forced_discharge ? 'Yes' : 'No';
                        document.getElementById('forced-charge').textContent = data.battery.forced_charge ? 'Yes' : 'No';
                        document.getElementById('target-price').textContent = parseFloat(data.battery.target_price_cents).toFixed(2) + ' Cents';
                        document.getElementById('target-electric-price').textContent = parseFloat(data.battery.target_electric_price_cents).toFixed(2) + ' Cents';
                    }
                    document.getElementById('last-updated').textContent = timeSince(data.last_updated);

                    // SOC & Solar
                    document.getElementById('soc').textContent = data.soc ? data.soc + '%' : 'n/a';
                    document.getElementById('remaining-solar').textContent = data.remaining_solar_generation_today ? parseFloat(data.remaining_solar_generation_today).toFixed(2) + ' kWh' : 'n/a';
                    document.getElementById('forecast-soc').textContent = data.forecast_soc ? data.forecast_soc + '%' : 'n/a';
                    document.getElementById('today-forecast').textContent = data.todayForecast ? parseFloat(data.todayForecast).toFixed(2) + ' kWh' : '0.00 kWh';
                    document.getElementById('tomorrow-forecast').textContent = data.tomorrowForecast ? parseFloat(data.tomorrowForecast).toFixed(2) + ' kWh' : '0.00 kWh';

                    // Sell Strategy
                    const sellStrategyContainer = document.getElementById('sell-strategy-container');
                    if (data.sellStrategy) {
                        if (data.sellStrategy.error) {
                            sellStrategyContainer.innerHTML = `<p class="text-danger">${data.sellStrategy.error}</p>`;
                        } else if (data.sellStrategy.message) {
                            sellStrategyContainer.innerHTML = `<p>${data.sellStrategy.message}</p>`;
                        } else {
                            sellStrategyContainer.innerHTML = 
                                `<p><strong>Total kWh to be sold:</strong> <span id="sell-kwh">${parseFloat(data.sellStrategy.total_kwh_sold || 0).toFixed(2)} kWh</span></p>` +
                                `<p><strong>Total Revenue:</strong> <span id="sell-revenue">$${(parseFloat(data.sellStrategy.total_revenue || 0) / 100).toFixed(2)}</span></p>` +
                                `<p><strong>Highest Sell Price:</strong> <span id="highest-sell-price">${parseFloat(data.sellStrategy.highest_sell_price || 0).toFixed(2)} c/kWh</span></p>` +
                                `<p><strong>Lowest Sell Price:</strong> <span id="lowest-sell-price">${parseFloat(data.sellStrategy.lowest_sell_price || 0).toFixed(2)} c/kWh</span></p>`;
                        }
                    } else {
                        sellStrategyContainer.innerHTML = `<p>No data available.</p>`;
                    }

                    // Buy Strategy
                    const buyStrategyContainer = document.getElementById('buy-strategy-container');
                    if (data.buyStrategy) {
                        if (data.buyStrategy.error) {
                            buyStrategyContainer.innerHTML = `<p class="text-danger">${data.buyStrategy.error}</p>`;
                        } else if (data.buyStrategy.message) {
                            buyStrategyContainer.innerHTML = `<p>${data.buyStrategy.message}</p>`;
                        } else {
                            buyStrategyContainer.innerHTML = 
                                `<p><strong>kWh to buy:</strong> <span id="buy-kwh">${parseFloat(data.kwh_to_buy || 0).toFixed(2)} kWh</span></p>` +
                                `<p><strong>Total Cost:</strong> <span id="buy-cost">$${(parseFloat(data.buyStrategy.total_cost || 0) / 100).toFixed(2)}</span></p>` +
                                `<p><strong>Total Revenue:</strong> <span id="buy-revenue">$${(parseFloat(data.buyStrategy.total_revenue || 0) / 100).toFixed(2)}</span></p>` +
                                `<p><strong>Estimated Profit:</strong> <span id="buy-profit">$${(parseFloat(data.buyStrategy.estimated_profit || 0) / 100).toFixed(2)}</span></p>` +
                                `<p><strong>Highest Buy Price:</strong> <span id="highest-buy-price">${parseFloat(data.buyStrategy.highest_buy_price || 0).toFixed(2)} c/kWh</span></p>` +
                                `<p><strong>Lowest Sell Price:</strong> <span id="lowest-sell-price">${parseFloat(data.buyStrategy.lowest_sell_price || 0).toFixed(2)} c/kWh</span></p>`;
                        }
                    } else {
                        buyStrategyContainer.innerHTML = `<p>No data available.</p>`;
                    }

                    // Transaction Log
                    const transactionLogBody = document.getElementById('transaction-log-body');
                    transactionLogBody.innerHTML = ''; // Clear existing rows
                    if (data.transactions && data.transactions.length > 0) {
                        let currentDate = null;
                        data.transactions.forEach(transaction => {
                            const transactionDate = new Date(transaction.datetime).toDateString();
                            if (currentDate !== transactionDate) {
                                currentDate = transactionDate;
                                const dateRow = document.createElement('tr');
                                dateRow.innerHTML = `<td colspan="3" class="text-center table-secondary"><strong>${new Date(transaction.datetime).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</strong></td>`;
                                transactionLogBody.appendChild(dateRow);
                            }
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${new Date(transaction.datetime).toLocaleTimeString()}</td>
                                <td>${transaction.action}</td>
                                <td>${parseFloat(transaction.price_cents).toFixed(2)}</td>
                            `;
                            transactionLogBody.appendChild(row);
                        });
                    } else {
                        transactionLogBody.innerHTML = '<tr><td colspan="3" class="text-center">No transactions found.</td></tr>';
                    }

                    timeRemaining = POLLING_INTERVAL / 1000;
                })
                .catch(error => console.error('Error fetching dashboard data:', error));
        }

        function updateCountdown() {
            timeRemaining--;
            if (timeRemaining < 0) timeRemaining = 0;
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('next-update-countdown').textContent = `${minutes}m ${seconds}s`;
        }

        setInterval(updateDashboard, POLLING_INTERVAL);
        setInterval(updateCountdown, 1000);

        updateDashboard();
        updateCountdown();
    });
</script>
@endpush
