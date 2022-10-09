<!DOCTYPE HTML>
<html>

<head>
    <script type="text/javascript" src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="https://canvasjs.com/assets/script/canvasjs.stock.min.js"></script>
    <script type="text/javascript">
        window.onload = function () {
            var dps1 = [], dps2 = [];
            var stockChart = new CanvasJS.StockChart("chartContainer", {
                theme: "light2",
                title: {
                    text: "{{ strtoupper($name) }}"
                },
                subtitles: [{
                    text: "Exponential Moving Average"
                }],
                charts: [{
                    axisX: {
                        valueFormatString: "HH:mm"
                    },
                    axisY: {
                        suffix: " usd"
                    },
                    toolTip: {
                        shared: true
                    },
                    legend: {
                        cursor: "pointer",
                        verticalAlign: "top",
                        itemclick: function (e) {
                            if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                                e.dataSeries.visible = false;
                            } else {
                                e.dataSeries.visible = true;
                            }
                            e.chart.render();
                        }
                    },
                    data: [{
                        type: "candlestick",
                        name: "Stock Price",
                        showInLegend: true,
                        xValueType: "dateTime",
                        xValueFormatString: "HH:mm",
                        dataPoints: dps1
                    }],
                }],
                navigator: {
                    data: [{
                        dataPoints: dps2
                    }],
                    slider: {
                        minimum: new Date(2018, 03, 01),
                        maximum: new Date(2018, 05, 01)
                    }
                }
            });
            $.getJSON("/api/klines?symbol={{ $name }}&interval=1m&limit=60", function (data) {
                for (var i = 0; i < data.length; i++) {
                    dps1.push({ x: new Date(data[i].date), y: [Number(data[i].open), Number(data[i].high), Number(data[i].low), Number(data[i].close)] });
                    dps2.push({ x: new Date(data[i].date), y: Number(data[i].close) });
                }
                stockChart.render();
                var ema7 = calculateEMA(dps1, 7);
                var ema25 = calculateEMA(dps1, 25);
                stockChart.charts[0].addTo("data", { type: "line", name: "EMA7", showInLegend: true, xValueType: "dateTime", xValueFormatString: "HH:mm", dataPoints: ema7 });
                stockChart.charts[0].addTo("data", { type: "line", name: "EMA25", showInLegend: true, xValueType: "dateTime", xValueFormatString: "HH:mm", dataPoints: ema25 });
            });
            function calculateEMA(dps, count) {
                var k = 2 / (count + 1);
                var emaDps = [{ x: dps[0].x, y: dps[0].y.length ? dps[0].y[3] : dps[0].y }];
                for (var i = 1; i < dps.length; i++) {
                    emaDps.push({ x: dps[i].x, y: (dps[i].y.length ? dps[i].y[3] : dps[i].y) * k + emaDps[i - 1].y * (1 - k) });
                }
                return emaDps;
            }
        }
    </script>
</head>

<body>
    <div id="chartContainer" style="height: 1000px; width: 100%;"></div>
</body>

</html>