document.addEventListener('DOMContentLoaded', function () {
    setInterval(function () {
        fetch('/latest-transactions')
            .then(response => response.json())
            .then(transactions => {
                let html = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date/Time</th>
                                <th>Price (cents)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                transactions.forEach(transaction => {
                    html += `
                        <tr>
                            <td>${transaction.id}</td>
                            <td>${transaction.datetime}</td>
                            <td>${transaction.price_cents}</td>
                            <td>${transaction.action}</td>
                        </tr>
                    `;
                });
                html += `
                        </tbody>
                    </table>
                `;
                document.getElementById('battery-transactions').innerHTML = html;
            });
    }, 5000);
});
