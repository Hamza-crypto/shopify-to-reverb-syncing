<!DOCTYPE html>
<html>
<head>
    <title>Chart Example</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="chart-container" style="position: relative; height:400px; width:800px">
    <canvas id="myChart"></canvas>
</div>

<script>

    document.addEventListener('DOMContentLoaded', function () {
        fetch('{{ route('chart.data') }}')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('myChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: Object.keys(data.createdData),
                        datasets: [{
                            label: 'Created',
                            borderColor: 'rgb(75, 192, 192)',
                            data: Object.values(data.createdData),
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
                                ticks: {
                                    maxTicksLimit: 23 // Set the maximum number of ticks on the x-axis
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Number of Orders'
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
