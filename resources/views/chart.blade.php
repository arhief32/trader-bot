<!DOCTYPE HTML>
<html>
<head>
</head>
<body>
<!-- candlestick -->
<div id="macd_chart" style="height: 1000px; width: 100%;"></div>
<script>
window.onload = function() {

var dps1 = [], dps2= [];
  var stockChart = new CanvasJS.StockChart("macd_chart",{
    theme: "light2",
    title:{
      text:"{{ strtoupper($name) }}"
    },
    subtitles: [{
      text: "Technical Indicators: MACD"
    }],
    height: 1500,
    charts: [{
      legend: {
        verticalAlign: "top",
        horizontalAlign: "left"
      },
      axisX: {
	    	valueFormatString: "HH:mm"
	    },
	    axisY: {
	    	suffix: " usd"
	    },
      data: [{
        type: "candlestick",
		    xValueType: "dateTime",
		    xValueFormatString: "HH:mm",
        dataPoints : dps1
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
  $.getJSON("/api/klines?symbol={{ $name }}&interval=1m&limit=60", function(data) {
    for(var i = 0; i < data.length; i++){
      dps1.push({x: new Date(data[i].date), y: [Number(data[i].open), Number(data[i].high), Number(data[i].low), Number(data[i].close)]});
      dps2.push({x: new Date(data[i].date), y: Number(data[i].close)});
    }
    stockChart.render();
    var ema12 = calculateEMA(dps1, 12),
        ema26 = calculateEMA(dps1, 26),
        macd = [], ema9;
    for(var i = 0; i < ema12.length; i++) {
      macd.push({x: ema12[i].x, y: (ema12[i].y - ema26[i].y)});
    }
    var ema9 = calculateEMA(macd, 9);
    stockChart.addTo("charts", {height: 500, data: [{type: "line", name: "MACD", showInLegend: true, dataPoints: macd}], legend: {horizontalAlign: "left"}, toolTip: {shared: true}});
    stockChart.charts[1].addTo("data", {type: "line", name: "Signal", showInLegend: true, dataPoints: ema9});
  });
  function calculateEMA(dps,mRange) {
    var k = 2/(mRange + 1);
    emaDps = [{x: dps[0].x, y: dps[0].y.length ? dps[0].y[3] : dps[0].y}];
    for (var i = 1; i < dps.length; i++) {
      emaDps.push({x: dps[i].x, y: (dps[i].y.length ? dps[i].y[3] : dps[i].y) * k + emaDps[i - 1].y * (1 - k)});
    }
    console.log(emaDps);
    return emaDps;
  }
 
}
</script>
<!-- end candlestick -->

<script type="text/javascript" src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="https://canvasjs.com/assets/script/canvasjs.stock.min.js"></script>

</body>
</html>    