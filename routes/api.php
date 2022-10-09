<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use App\Models\MACD;
use App\Models\EMA;
use App\Models\RSI;

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

Route::get('test-rsi', function(Request $request){
    $klines = requestKlines($request->symbol, $request->interval, $request->limit);
    
    // calculate rsi
    $rsi = RSI::run($klines);

    return response()->json($rsi);
});

Route::get('test-macd', function(Request $request){
    $klines = requestKlines($request->symbol, $request->interval, $request->limit);    
    
    // calculate macd
    // $macd = MACD::run($klines);
    $macd = MACD::calculate($klines);

    // return response()->json($klines);
    return response()->json($macd);
    
});

Route::get('test-ema', function(Request $request){
    $klines = requestKlines($request->symbol, $request->interval, $request->limit);    
    $data = [];
    foreach($klines as $row_klines){
        array_push($data, $row_klines['close']);
    }

    // calculate macd
    $ema = EMA::calculate($klines, 7, 25);
    // $ema7 = trader_ema($data, 7);
    // $ema25 = trader_ema($data, 25);

    // foreach($klines as $key => $value){
    //     isset($ema7[$key]) ? $klines[$key]['ema']['ema7'] = $ema7[$key] : $klines[$key]['ema']['ema7'] = 0;
    //     isset($ema25[$key]) ? $klines[$key]['ema']['ema25'] = $ema25[$key] : $klines[$key]['ema']['ema25'] = 0;
    // }

    return response()->json($ema);
});

Route::get('exchange-info', function(){
    return requestExchangeInfo();
});

Route::get('set-precision', function(){
    setPrecision();
});

Route::get('save-asset', function(){
    $exchange_info = requestExchangeInfo();
    $assets = $exchange_info->symbols;
    $array_assets = [];
    foreach($assets as $row_asset){
        $row_asset->quoteAsset == 'USDT' ? array_push($array_assets, $row_asset->symbol) : false;
    }
    updateRedis('LIST_ASSETS', $array_assets);
});

Route::get('ticker', function(){
    return requestTicker('BTCUSDT')->lastPrice;
});

Route::get('klines', function(Request $request){
    return response()->json(requestKlines($request->symbol, $request->interval, $request->limit));
});

Route::get('account', function(){
    return requestAccountInformation();
});

Route::get('position-risk', function(){
    return response()->json(requestPositionRisk());
});

Route::post('new-order', function(Request $request){
    // return response()->json(requestTradeNewOrder($request->symbol, $request->side, $request->amount));
    return response()->json(requestTradeNewOrder($request->symbol, $request->side, $request->amount));
});

Route::post('take-profit', function(Request $request){
    return response()->json(requestTakeProfit($request->symbol, $request->side, $request->tp_percent));
});

Route::post('batch-orders', function(Request $request){
    return response()->json(requestMultipleOrders($request->symbol, $request->side, $request->amount, $request->tp_percent));
});

Route::get('check-position', function(){
    return getPrefixRedis('POSITION_RISK_*');
});