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
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Current Prices</h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Electricity price:</strong> <span id="electricity-price" class="{{ getPriceClass(isset($prices['electricityPrice']) ? $prices['electricityPrice'] : null) }}">{{ isset($prices['electricityPrice']) ? $prices['electricityPrice'] . ' c/kWh' : 'n/a' }}</span></p>
                    <p class="card-text"><strong>Solar Feed-in Tariff:</strong> <span id="solar-ftt" class="{{ getPriceClass(isset($prices['solarPrice']) ? $prices['solarPrice'] : null) }}">{{ isset($prices['solarPrice']) ? $prices['solarPrice'] . ' c/kWh' : 'n/a' }}</span></p>
                </div>
            </div>
        </div>

        {{-- Battery Settings Card --}}
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Live Battery Settings</h5>
                </div>
                <div class="card-body">
                    @if ($battery)
                        <p class="card-text"><strong>Target price:</strong> <span id="target-price">{{ $battery->target_price_cents }}</span> cents</p>
                        <p class="card-text"><strong>Forced Discharge:</strong> <span id="forced-discharge">{{ $battery->forced_discharge ? 'Yes' : 'No' }}</span></p>
                    @else
                        <p class="card-text">No battery settings found.</p>
                    @endif
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
                                        <td class="{{ getPriceClass($t->price_cents) }}">{{ $t->price_cents }}</td>
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

        function getPriceClass(price) {
            if (price === null || price === undefined) return '';
            if (price > 0) return 'text-success';
            if (price < 0) return 'text-danger';
            return 'text-muted';
        }

        function updateDashboard() {
            fetch('{{ route("dashboard.data") }}')
                .then(response => response.json())
                .then(data => {
                    // Update Prices
                    if (data.prices) {
                        electricityPriceEl.textContent = data.prices.electricityPrice !== null ? data.prices.electricityPrice + ' c/kWh' : 'n/a';
                        electricityPriceEl.className = getPriceClass(data.prices.electricityPrice);
                        solarFttEl.textContent = data.prices.solarPrice !== null ? data.prices.solarPrice + ' c/kWh' : 'n/a';
                        solarFttEl.className = getPriceClass(data.prices.solarPrice);
                    } else {
                        electricityPriceEl.textContent = 'n/a';
                        electricityPriceEl.className = '';
                        solarFttEl.textContent = 'n/a';
                        solarFttEl.className = '';
                    }

                    // Update Battery Settings
                    if (data.battery) {
                        targetPriceEl.textContent = data.battery.target_price_cents;
                        forcedDischargeEl.textContent = data.battery.forced_discharge ? 'Yes' : 'No';
                    }

                    // Update Transactions
                    transactionsBodyEl.innerHTML = ''; // Clear existing transactions
                    if (data.transactions && data.transactions.length > 0) {
                        data.transactions.forEach(t => {
                            const row = `<tr>
                                <td>${t.datetime}</td>
                                <td>${t.action.charAt(0).toUpperCase() + t.action.slice(1)}</td>
                                <td class="${getPriceClass(t.price_cents)}">${t.price_cents}</td>
                            </tr>`;
                            transactionsBodyEl.innerHTML += row;
                        });
                    } else {
                        const row = `<tr><td colspan="3" class="text-center">No recent transactions found.</td></tr>`;
                        transactionsBodyEl.innerHTML = row;
                    }
                })
                .catch(error => console.error('Error fetching dashboard data:', error));
        }

        // Fetch data every 5 seconds
        setInterval(updateDashboard, 5000);
    });
</script>
@endpush
