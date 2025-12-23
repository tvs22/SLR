@extends('layouts.app')

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
                    <p class="card-text"><strong>Electricity price:</strong> <span id="electricity-price">{{ isset($prices['electricityPrice']) ? $prices['electricityPrice'] . ' c/kWh' : 'n/a' }}</span></p>
                    <p class="card-text"><strong>Solar Feed-in Tariff:</strong> <span id="solar-ftt">{{ isset($prices['solarPrice']) ? $prices['solarPrice'] . ' c/kWh' : 'n/a' }}</span></p>
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
                                        <td>{{ $t->price_cents }}</td>
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

        let lastPrices = null;

        function updateDashboard() {
            fetch('{{ route("dashboard.data") }}')
                .then(response => response.json())
                .then(data => {
                    // Only update if the prices have changed
                    if (JSON.stringify(data.prices) !== lastPrices) {
                        lastPrices = JSON.stringify(data.prices);

                        // Update Prices
                        electricityPriceEl.textContent = data.prices && data.prices.electricityPrice ? data.prices.electricityPrice + ' c/kWh' : 'n/a';
                        solarFttEl.textContent = data.prices && data.prices.solarPrice ? data.prices.solarPrice + ' c/kWh' : 'n/a';

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
                                    <td>${t.price_cents}</td>
                                </tr>`;
                                transactionsBodyEl.innerHTML += row;
                            });
                        } else {
                            const row = `<tr><td colspan="3" class="text-center">No recent transactions found.</td></tr>`;
                            transactionsBodyEl.innerHTML = row;
                        }
                    }
                })
                .catch(error => console.error('Error fetching dashboard data:', error));
        }

        // Fetch data every 5 seconds
        setInterval(updateDashboard, 5000);

        // Initial call to populate data
        updateDashboard();
    });
</script>
@endpush
