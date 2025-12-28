<div class="card">
    <div class="card-header">
        <h5>Recent Battery Transactions</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Time</th>
                    <th scope="col">Price (cents)</th>
                    <th scope="col">kWh</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentDate = null;
                @endphp
                @foreach ($batteryTransactions as $transaction)
                    @php
                        $transactionDate = \Carbon\Carbon::parse($transaction->datetime)->format('Y-m-d');
                    @endphp
                    @if ($transactionDate !== $currentDate)
                        @php
                            $currentDate = $transactionDate;
                        @endphp
                        <tr>
                            <td colspan="4" class="text-center bg-light">
                                <strong>{{ \Carbon\Carbon::parse($transaction->datetime)->format('l, j F Y') }}</strong>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->datetime)->format('H:i') }}</td>
                        <td>{{ $transaction->price_cents }}</td>
                        <td>{{ number_format($transaction->kwh, 2) }}</td>
                        <td>{{ $transaction->action }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
