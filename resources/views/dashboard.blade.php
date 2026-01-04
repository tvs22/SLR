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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Battery Dashboard</h1>
        <button id="predict-prices-btn" class="btn btn-primary">Predict Prices</button>
    </div>

    <ul class="nav nav-tabs" id="plan-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="buy-plan-tab" data-bs-toggle="tab" data-bs-target="#buy-plan-content" type="button" role="tab" aria-controls="buy-plan-content" aria-selected="true">Buy Plan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="status-tab" data-bs-toggle="tab" data-bs-target="#status-content" type="button" role="tab" aria-controls="status-content" aria-selected="false">Status</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sell-plan-tab" data-bs-toggle="tab" data-bs-target="#sell-plan-content" type="button" role="tab" aria-controls="sell-plan-content" aria-selected="false">Sell Plan</button>
        </li>
    </ul>

    <div class="tab-content" id="plan-tabs-content">
        <div class="tab-pane fade" id="buy-plan-content" role="tabpanel" aria-labelledby="buy-plan-tab">
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Essential Buy Plan (to 20kWh)</h5>
                             <p class="mb-0">Remaining to buy: <span id="essential-kwh-to-buy"></span></p>
                        </div>
                        <div class="card-body" id="essential-buy-plan-container">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Price (c/kWh)</th>
                                        <th>kWh to Buy</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="essential-buy-plan-body">
                                    {{-- Content will be injected by JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h5>Target Buy Plan</h5>
                             <p class="mb-0">Remaining to buy: <span id="target-kwh-to-buy"></span></p>
                        </div>
                        <div class="card-body" id="target-buy-plan-container">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Price (c/kWh)</th>
                                        <th>kWh to Buy</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody id="target-buy-plan-body">
                                    {{-- Content will be injected by JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show active" id="status-content" role="tabpanel" aria-labelledby="status-tab">
            <div class="row mt-4">
                {{-- Prices & Status --}}
                <div class="col-lg-6 col-md-12 mb-4">
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
                <div class="col-lg-6 col-md-12 mb-4">
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
            </div>
            {{-- Battery Transactions --}}
            <div class="row mt-4">
                <div class="col-12">
                    <div id="battery-transactions-container">
                        @include('partials.battery-transactions')
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="sell-plan-content" role="tabpanel" aria-labelledby="sell-plan-tab">
            <div class="row mt-4">
                {{-- Sell Strategies --}}
                <div class="col-lg-12 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5>Sell Strategies</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-danger text-white">
                                            <h6>{{$batteryStrategies[0]->name}} ({{$batteryStrategies[0]->sell_start_time}} - {{$batteryStrategies[0]->sell_end_time}})</h6>
                                            <small>Sell down to {{$batteryStrategies[0]->soc_lower_bound}}% SOC</small>
                                        </div>
                                        <div class="card-body" id="evening-sell-strategy-container">
                                            {{-- Content will be injected by JS --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-warning text-dark">
                                            <h6>{{$batteryStrategies[1]->name}} ({{$batteryStrategies[1]->sell_start_time}} - {{$batteryStrategies[1]->sell_end_time}})</h6>
                                            <small>Sell down to {{$batteryStrategies[1]->soc_lower_bound}}% SOC</small>
                                        </div>
                                        <div class="card-body" id="late-evening-sell-strategy-container">
                                            {{-- Content will be injected by JS --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6>{{$batteryStrategies[2]->name}} ({{$batteryStrategies[2]->sell_start_time}} - {{$batteryStrategies[2]->sell_end_time}})</h6>
                                            <small>Sell down to {{$batteryStrategies[2]->soc_lower_bound}}% SOC</small>
                                        </div>
                                        <div class="card-body" id="late-night-sell-strategy-container">
                                            {{-- Content will be injected by JS --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Sell Plan (30-Min Intervals)</h5>
                        </div>
                        <div class="card-body" id="sell-plan-container">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Price (c/kWh)</th>
                                        <th>kWh to Sell</th>
                                        <th>Revenue</th>
                                        <th>Remaining kWh</th>
                                    </tr>
                                </thead>
                                <tbody id="sell-plan-body">
                                    {{-- Content will be injected by JS --}}
                                </tbody>
                            </table>
                        </div>
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
        let nextPollTimestamp = null;
        function getPriceClass(price) {
            if (price === null || price === undefined) return '';
            if (price > 0) return 'text-success';
            if (price < 0) return 'text-danger';
            return 'text-muted';
        }

        function timeSince(date) {
            if (!date) return 'n/a';
            const d = new Date(date);
            const hours = d.getHours().toString().padStart(2, '0');
            const minutes = d.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        function renderSellStrategy(containerId, strategyData) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (strategyData && strategyData.sell_plan && strategyData.sell_plan.length > 0) {
                let html = '<ul class="list-group list-group-flush">';
                strategyData.sell_plan.forEach(item => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">` +
                            `<span>${item.time} -> ${item.price.toFixed(2)}c</span>` +
                            `<span class="badge bg-primary rounded-pill">${item.kwh.toFixed(2)} kWh</span>` +
                            `</li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
            } else if (strategyData && strategyData.message) {
                container.innerHTML = `<p>${strategyData.message}</p>`;
            } else {
                container.innerHTML = '<p>No profitable slots found.</p>';
            }
        }

        function renderBuyPlan(containerId, bodyId, planData) {
            const container = document.getElementById(containerId);
            const body = document.getElementById(bodyId);
            body.innerHTML = '';
            let totalKwh = 0;

            if (planData) {
                if (planData.error) {
                    container.innerHTML = `<p class="text-danger">${planData.error}</p>`;
                } else if (planData.message) {
                    container.innerHTML = `<p>${planData.message}</p>`;
                } else if (planData.buy_plan && planData.buy_plan.length > 0) {
                    planData.buy_plan.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${item.time}</td>
                            <td>${item.price.toFixed(2)}</td>
                            <td>${item.kwh.toFixed(2)}</td>
                            <td>$${(item.cost / 100).toFixed(2)}</td>
                        `;
                        body.appendChild(row);
                        totalKwh += item.kwh;
                    });
                } else {
                    body.innerHTML = '<tr><td colspan="4" class="text-center">No buy plan available.</td></tr>';
                }
            } else {
                body.innerHTML = '<tr><td colspan="4" class="text-center">No data available.</td></tr>';
            }

            const existingTfoot = body.parentElement.querySelector('tfoot');
            if (existingTfoot) {
                existingTfoot.remove();
            }

            const tfoot = document.createElement('tfoot');
            tfoot.innerHTML = `
                <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td><strong>${totalKwh.toFixed(2)}</strong></td>
                    <td></td>
                </tr>
            `;
            body.parentElement.appendChild(tfoot);
        }

        function renderDashboard(data) {
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

            // Buy Plans
            document.getElementById('essential-kwh-to-buy').textContent = data.kwh_to_buy_essential ? parseFloat(data.kwh_to_buy_essential).toFixed(2) + ' kWh' : '0.00 kWh';
            document.getElementById('target-kwh-to-buy').textContent = data.kwh_to_buy_target ? parseFloat(data.kwh_to_buy_target).toFixed(2) + ' kWh' : '0.00 kWh';
            renderBuyPlan('essential-buy-plan-container', 'essential-buy-plan-body', data.essential_buy_plan);
            renderBuyPlan('target-buy-plan-container', 'target-buy-plan-body', data.target_buy_plan);

            // Sell Plan
            const sellPlanBody = document.getElementById('sell-plan-body');
            sellPlanBody.innerHTML = '';
            const sellPlans = [];
            if (data.evening_sell_strategy && data.evening_sell_strategy.sell_plan) {
                sellPlans.push(...data.evening_sell_strategy.sell_plan);
            }
            if (data.late_evening_sell_strategy && data.late_evening_sell_strategy.sell_plan) {
                sellPlans.push(...data.late_evening_sell_strategy.sell_plan);
            }
            if (data.late_night_sell_strategy && data.late_night_sell_strategy.sell_plan) {
                sellPlans.push(...data.late_night_sell_strategy.sell_plan);
            }

            if (sellPlans.length > 0) {
                sellPlans.sort((a, b) => {
                    return a.time.localeCompare(b.time);
                });

                let totalKwhToSell = sellPlans.reduce((total, item) => total + item.kwh, 0);

                sellPlans.forEach(item => {
                    totalKwhToSell -= item.kwh;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.time}</td>
                        <td>${item.price.toFixed(2)}</td>
                        <td>${item.kwh.toFixed(2)}</td>
                        <td>$${(item.revenue / 100).toFixed(2)}</td>
                        <td>${totalKwhToSell.toFixed(2)}</td>
                    `;
                    sellPlanBody.appendChild(row);
                });
            } else {
                sellPlanBody.innerHTML = '<tr><td colspan="5" class="text-center">No sell plan available.</td></tr>';
            }

            // Sell Strategies
            renderSellStrategy('evening-sell-strategy-container', data.evening_sell_strategy);
            renderSellStrategy('late-evening-sell-strategy-container', data.late_evening_sell_strategy);
            renderSellStrategy('late-night-sell-strategy-container', data.late_night_sell_strategy);

            // Battery Transactions
            const transactionsContainer = document.getElementById('battery-transactions-container');
            if (data.batteryTransactions && data.batteryTransactions.length > 0) {
                const groupedTransactions = data.batteryTransactions.reduce((acc, transaction) => {
                    const date = new Date(transaction.datetime).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
                    if (!acc[date]) {
                        acc[date] = [];
                    }
                    acc[date].push(transaction);
                    return acc;
                }, {});

                let transactionsHtml = '<div class="card"><div class="card-header"><h5>Recent Battery Transactions</h5></div><div class="card-body">';

                for (const date in groupedTransactions) {
                    transactionsHtml += `<h6 class="mt-3">${date}</h6>`;
                    transactionsHtml += '<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Time</th><th>Action</th><th>Price (c/kWh)</th></tr></thead><tbody>';

                    groupedTransactions[date].forEach(transaction => {
                        transactionsHtml += `
                            <tr>
                                <td>${new Date(transaction.datetime).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })}</td>
                                <td>${transaction.action}</td>
                                <td>${parseFloat(transaction.price_cents).toFixed(2)}</td>
                            </tr>`;
                    });
                    transactionsHtml += '</tbody></table></div>';
                }
                transactionsHtml += '</div></div>';
                transactionsContainer.innerHTML = transactionsHtml;
            } else {
                transactionsContainer.innerHTML = '<div class="card"><div class="card-header"><h5>Recent Battery Transactions</h5></div><div class="card-body"><p>No recent transactions.</p></div></div>';
            }
        }

        function pollDashboard() {
             fetch('{{ route("dashboard.data") }}')
                .then(response => response.json())
                .then(data => {
                    renderDashboard(data)
                })
                .catch(error => console.error('Error fetching dashboard data:', error))
                .finally(() => {
                    // Schedule the next poll only after the current one is finished
                    scheduleNextPoll();
                });
        }

        function getNextPollTime() {
            const now = new Date();
            const minutes = now.getMinutes();
            const intervals = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55];
            let nextMinute = intervals.find(i => i > minutes);

            if (nextMinute === undefined) {
                nextMinute = 0;
            }

            const nextPollTime = new Date();
            nextPollTime.setMinutes(nextMinute);
            nextPollTime.setSeconds(45);

            if (nextMinute === 0 && minutes > 55) {
                nextPollTime.setHours(now.getHours() + 1);
            }
            return nextPollTime;
        }

        function scheduleNextPoll() {
            const nextPoll = getNextPollTime();
            nextPollTimestamp = nextPoll.getTime();
            const delay = nextPollTimestamp - Date.now();

            setTimeout(() => {
                pollDashboard();
            }, delay);
        }

        function updateCountdown() {
            const nextPollTime = new Date(nextPollTimestamp);
            const now = new Date();
            let timeRemaining = Math.round((nextPollTime.getTime() - now.getTime()) / 1000);

            if (timeRemaining < 0) {
                timeRemaining = 0;
            }

            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const countdownElement = document.getElementById('next-update-countdown');
            if(countdownElement) {
                countdownElement.textContent = `${minutes}m ${seconds}s`;
            }
        }

        document.getElementById('predict-prices-btn').addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Predicting...';
            fetch('/api/price/predicted-prices', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(() => {
                pollDashboard(); 
            })
            .finally(() => {
                const button = document.getElementById('predict-prices-btn');
                button.disabled = false;
                button.innerHTML = 'Predict Prices';
            });
        });

        
        setInterval(updateCountdown, 1000);

        pollDashboard();
    });
</script>
@endpush
