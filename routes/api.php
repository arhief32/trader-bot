<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use App\Models\MACD;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test-redis', function(){
    Redis::set('key1', 'test-redis');
});

Route::get('test-rsi', function(){
    $test_array = [0.4566,0.4567,0.457,0.4572,0.4573,0.4573,0.4574,0.4573,0.4571,0.457,0.4571,0.4574,0.4574,0.4574,0.4575,0.4575,0.4576,0.4576,0.4576];
    $test_rsi = trader_rsi($test_array, 6);
    return response()->json([
        'data' => [$test_array[0], $test_array[1], $test_array[2], $test_array[3], $test_array[4], $test_array[5]],
        'rsi' => $test_rsi,
    ]);
});

Route::get('test-macd', function(Request $request){
    $symbol = $request->symbol;
    $data = getRedis('MARKET_'.$symbol);

    $array_macd = [];

    foreach($data as $row){
        array_push($array_macd, [
            'close' => (float)$row,
            'ema_12' => 0,
            'ema_26' => 0,
            'macd' => 0,
            'signal' => 0,
            'histogram' => 0,
        ]);
    }

    // ema12
    $data_12 = array_slice($data, 0, 12);
    $average_12 = array_sum($data_12) / count($data_12);
    $array_macd[11]['ema_12'] = $average_12;
    $next_data_12 = array_slice($data, 12);
    foreach($next_data_12 as $key_nd12 => $value_nd12){
        $value_ema_12 = (float)$array_macd[12+$key_nd12]['close']*(2/(12+1))+$array_macd[12+$key_nd12-1]['ema_12']*(1-(2/(12+1)));
        $array_macd[12+$key_nd12]['ema_12'] = $value_ema_12;
    }

    // ema26
    $data_26 = array_slice($data, 0, 26);
    $average_26 = array_sum($data_26) / count($data_26);
    $array_macd[25]['ema_26'] = $average_26;
    $next_data_26 = array_slice($data, 26);
    foreach($next_data_26 as $key_nd26 => $value_nd26){
        $value_ema_26 = (float)$array_macd[26+$key_nd26]['close']*(2/(26+1))+$array_macd[26+$key_nd26-1]['ema_26']*(1-(2/(26+1)));
        $array_macd[26+$key_nd26]['ema_26'] = $value_ema_26;        
    }

    // macd
    foreach($array_macd as $key_macd =>$row_macd){
        if($row_macd['ema_12'] != 0 && $row_macd['ema_26'] != 0){
            $array_macd[$key_macd]['macd'] = $row_macd['ema_12'] - $row_macd['ema_26'];
        }
    }

    // signal
    $data_macd = array_slice($array_macd, 25, 9);
    $data_9 = [];
    foreach($data_macd as $row_data_macd){
        array_push($data_9, $row_data_macd['macd']);
    }
    $average_9 = array_sum($data_9) / count($data_9);
    $array_macd[33]['signal'] = $average_9;    
    $next_data_9 = array_slice($array_macd, 34);
    foreach($next_data_9 as $key_nd9 => $value_nd9){
        $value_signal = (float)$array_macd[34+$key_nd9]['macd']*(2/(9+1))+$array_macd[9+$key_nd9-1]['signal']*(1-(2/(9+1)));
        $array_macd[34+$key_nd9]['signal'] = $value_signal;
    }
    
    // histogram
    foreach($array_macd as $key_macd =>$row_macd){
        if($row_macd['macd'] != 0 && $row_macd['signal'] != 0){
            $array_macd[$key_macd]['histogram'] = $row_macd['macd'] - $row_macd['signal'];
        }
    }

    return response()->json($array_macd);
    
});

Route::get('exchange-info', function(){
    return requestExchangeInfo();
});

Route::get('set-precision', function(){
    setPrecision();
});

Route::get('ticker', function(){
    return requestTicker('BTCUSDT')->lastPrice;
});

Route::get('account', function(){
    return requestAccountInformation();
});

Route::post('new-order', function(Request $request){
    return response()->json(requestTradeNewOrder($request->symbol, $request->side, $request->amount));
});

Route::post('take-profit', function(Request $request){
    return response()->json(requestTakeProfit($request->symbol, $request->side, $request->tp_percent));
});