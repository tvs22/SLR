@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Simulate Sell Strategy</h1>
    <form id="simulation-form">
        <div class="form-group">
            <label for="soc">State of Charge (SOC)</label>
            <input type="number" class="form-control" id="soc" name="soc" min="0" max="100" required>
        </div>
        <div class="form-group">
            <label for="time">Time</label>
            <input type="datetime-local" class="form-control" id="time" name="time" required>
        </div>
        <button type="submit" class="btn btn-primary">Simulate</button>
    </form>

    <div id="simulation-placeholder" class="mt-5"></div>

    <div id="simulation-results-container" class="mt-5" style="display: none;">
        <h2>Simulation Results</h2>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Summary</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Total kWh Sold:</strong> <span id="total-kwh-sold"></span> kWh</p>
                        <p><strong>Total Revenue:</strong> $<span id="total-revenue"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Highest Sell Price:</strong> <span id="highest-sell-price"></span> cents/kWh at <span id="highest-sell-price-time"></span></p>
                        <p><strong>Lowest Sell Price:</strong> <span id="lowest-sell-price"></span> cents/kWh</p>
                    </div>
                </div>
            </div>
        </div>

        <h3>Sell Plan Details</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Time</th>
                    <th scope="col">Price (c/kWh)</th>
                    <th scope="col">kWh to Sell</th>
                    <th scope="col">Est. Revenue</th>
                </tr>
            </thead>
            <tbody id="sell-plan-table-body">
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('simulation-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const soc = document.getElementById('soc').value;
        const time = document.getElementById('time').value;
        const placeholder = document.getElementById('simulation-placeholder');
        const resultsContainer = document.getElementById('simulation-results-container');

        // Reset view
        resultsContainer.style.display = 'none';
        placeholder.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>';

        const params = new URLSearchParams({ soc, time });

        fetch(`/api/price/simulate?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.text().then(text => text ? JSON.parse(text) : null);
        })
        .then(data => {
            placeholder.innerHTML = ''; // Clear spinner/previous message

            if (!data || !Array.isArray(data.sell_plan) || data.sell_plan.length === 0) {
                let message = data?.message || "No profitable sell slots found.";
                placeholder.innerHTML = `<div class="alert alert-info">${message}</div>`;
                return;
            }

            // Populate summary
            document.getElementById('total-kwh-sold').textContent = data.total_kwh_sold.toFixed(2);
            document.getElementById('total-revenue').textContent = (data.total_revenue / 100).toFixed(2); // Assuming revenue is in cents
            document.getElementById('highest-sell-price').textContent = data.highest_sell_price.toFixed(2);
            document.getElementById('highest-sell-price-time').textContent = new Date(data.highest_sell_price_time).toLocaleTimeString();
            document.getElementById('lowest-sell-price').textContent = data.lowest_sell_price.toFixed(2);

            // Populate sell plan table
            const tableBody = document.getElementById('sell-plan-table-body');
            tableBody.innerHTML = ''; // Clear previous results
            data..forEach(slot => {
                const revenue = (slot.price * slot.kwh) / 100; // revenue in dollars
                const row = `<tr>
                    <td>${new Date(slot.time).toLocaleTimeString()}</td>
                    <td>${slot.price.toFixed(2)}</td>
                    <td>${slot.kwh.toFixed(2)}</td>
                    <td>$${revenue.toFixed(2)}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });

            // Show results
            resultsContainer.style.display = 'block';
        })
        .catch(error => {
            let errorMessage = error.message || 'An unexpected error occurred.';
            if (error.errors) {
                errorMessage += '<ul>';
                for (const field in error.errors) {
                    errorMessage += `<li>${error.errors[field].join(', ')}</li>`;
                }
                errorMessage += '</ul>';
            } else if (error.details) {
                 errorMessage += `: ${error.details}`;
            }

            placeholder.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${errorMessage}</div>`;
            console.error('Error:', error);
        });
    });
</script>
@endpush
