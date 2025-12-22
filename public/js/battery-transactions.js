document.addEventListener('DOMContentLoaded', function () {
    setInterval(function () {
        fetch('/latest-transactions')
            .then(response => response.text())
            .then(html => {
                document.getElementById('battery-transactions').innerHTML = html;
            });
    }, 5000);
});
