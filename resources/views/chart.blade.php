<!DOCTYPE html>
<html>

<head>
    <title>Chart Example</title>
    <!-- <meta http-equiv="refresh" content="3;"> -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="chart-container" style="position: relative; height:400px; width:1200px">
        <canvas id="myChart"></canvas>
    </div>

    <form action="{{ route('chart.store') }}" method="POST">
        @csrf();
        @method('POST')
        <label for="amount">Amount</label>
        <input type="number" name="amount">

        <input type="submit" value="Submit">
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        const urlParams = new URLSearchParams(window.location.search);
        const monthValue = urlParams.get('month') || 12; 
        fetch(`{{ route('chart.data') }}?month=${monthValue}`)
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('myChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Created',
                            borderColor: 'rgb(75, 192, 192)',
                            data: Object.values(data.data),
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Date'
                                },
                               
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Amount'
                                }
                            }
                        }
                    }
                });

            });
    });
    </script>
</body>

</html>