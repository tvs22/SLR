@if (!empty($sell_plan) && !empty($sell_plan['sell_plan']))
    <div class="card">
        <div class="card-header">
            {{ $plan_name }}
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Price (c/kWh)</th>
                        <th>kWh to Sell</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sell_plan['sell_plan'] as $interval)
                        <tr>
                            <td>{{ $interval['time'] }}</td>
                            <td>{{ number_format($interval['price'], 2) }}</td>
                            <td>{{ number_format($interval['kwh'], 2) }}</td>
                            <td>${{ number_format($interval['revenue'] / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Totals</th>
                        <th>{{ number_format($sell_plan['total_kwh_sold'], 2) }} kWh</th>
                        <th>${{ number_format($sell_plan['total_revenue'] / 100, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header">
            {{ $plan_name }}
        </div>
        <div class="card-body">
             <p>No sell plan available.</p>
        </div>
    </div>
@endif
