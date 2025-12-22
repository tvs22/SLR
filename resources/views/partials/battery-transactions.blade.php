<table class="table">
    <thead>
        <tr>
            <th scope="col">Timestamp</th>
            <th scope="col">Price (cents)</th>
            <th scope="col">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($batteryTransactions as $transaction)
            <tr>
                <td>{{ $transaction->datetime }}</td>
                <td>{{ $transaction->price_cents }}</td>
                <td>{{ $transaction->action }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
