<canvas id="line" style="width: 100%;height: 262px"></canvas>
<script>
    $(function () {
        var recharge_l = {!! $recharge_l !!},
            recharge_d = {!! $recharge_d !!};
        console.log(recharge_l);
        function randomScalingFactor() {
            return Math.floor(Math.random() * 100)
        }
        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: 'rgb(75, 192, 192)',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };
        var config = {
            type: 'line',
            data: {
                labels: recharge_l,
                datasets: [{
                    label: '充值',
                    backgroundColor: window.chartColors.red,
                    borderColor: window.chartColors.red,
                    data: recharge_d,
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: '充值趋势'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: '日期'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: '金币'
                        }
                    }]
                }
            }
        };
        var ctx = document.getElementById('line').getContext('2d');
        new Chart(ctx, config);
    });
</script>
