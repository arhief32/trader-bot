<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

function testFunction()
{
    return 'masuk';
}

function requestExchangeInfo()
{
    // request to get price real-time
    $client_exchange_info = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_exchange_info = $client_exchange_info->request('GET', '/fapi/v1/exchangeInfo');
    $response_exchange_info = json_decode($request_exchange_info->getBody());
    return $response_exchange_info;
}

function setPrecision()
{
    $exchange_info = requestExchangeInfo();
    $precision_list = $exchange_info->symbols;

    foreach($precision_list as $symbol){
        $data = [
            'price_precision' => $symbol->pricePrecision,
            'quantity_precision' => $symbol->quantityPrecision,
        ];
        updateRedis('PRECISION_'.$symbol->symbol, $data);
    }
}

function getPrecision($symbol)
{
    return getRedis('PRECISION_'.$symbol);
}

function diffOrderPrice(int $price_precision)
{
    switch ($price_precision) {
        case 1:
            return (float)5;
            break;
        case 2:
            return (float)0.5;
            break;
        case 3:
            return (float)0.05;
            break;
        case 4:
            return (float)0.005;
            break;
        case 5:
            return (float)0.0005;
            break;
        case 6:
            return (float)0.00005;
            break;
        case 7:
            return (float)0.000005;
            break;
        case 8:
            return (float)0.0000005;
            break;
        case 9:
            return (float)0.00000005;
            break;
        default:
            return (float)0.000000005;
    }
}

function requestTicker($asset)
{
    // request to get price real-time
    $client_ticker = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_ticker = $client_ticker->request('GET', '/fapi/v1/ticker/24hr', [
        'query' => [
            'symbol' => $asset,
        ],
    ]);
    $response_ticker = json_decode($request_ticker->getBody());
    return $response_ticker;
}

function requestKlines($asset, $interval, $limit)
{
    // request to get price real-time
    $client_klines = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_klines = $client_klines->request('GET', '/fapi/v1/klines', [
        'query' => [
            'symbol' => $asset,
            'interval' => $interval,
            'limit' => $limit,
        ],
    ]);
    $response_klines = json_decode($request_klines->getBody());
    
    // define data
    $data = [];
    foreach($response_klines as $row_klines){
        array_push($data, [
            'date' => date('Y-m-d H:i:s', (int)$row_klines[0]/1000),
            'open' => (float)$row_klines[1],
            'high' => (float)$row_klines[2],
            'low' => (float)$row_klines[3],
            'close' => (float)$row_klines[4],
        ]);
    }
    
    return $data;
}

function signature($query_string, $secret) {
    return hash_hmac('sha256', $query_string, $secret);
}

function requestAccountInformation()
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    $timestamp = intval(microtime(true) * 1000);    
    $query=[
        'timestamp' => $timestamp,
    ];
    $query_string = http_build_query($query);
    
    // generate signature
    $signature = signature($query_string, $api_secret);
    
    // insert signature to query params
    $query['signature'] = $signature;
    
    // request to get price real-time
    $client_account_information = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_account_information = $client_account_information->request('GET', '/fapi/v2/account', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_account_information = json_decode($request_account_information->getBody());
    return $response_account_information;
}

function requestPositionRisk()
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    $timestamp = intval(microtime(true) * 1000);    
    $query=[
        'timestamp' => $timestamp,
    ];
    $query_string = http_build_query($query);
    
    // generate signature
    $signature = signature($query_string, $api_secret);
    
    // insert signature to query params
    $query['signature'] = $signature;
    
    // request to get price real-time
    $client_account_information = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_account_information = $client_account_information->request('GET', '/fapi/v2/positionRisk', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_account_information = json_decode($request_account_information->getBody());
    return $response_account_information;
}

function requestTradeNewOrder($symbol, $side, $amount)
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    
    // define data
    $amount = (float)$amount;
    $last_price = (float)requestTicker($symbol)->lastPrice;
    $precision = getPrecision($symbol);
    $price_precision = $precision->price_precision;
    $quantity_precision = $precision->quantity_precision;
    $quantity = round($amount/$last_price, $quantity_precision);
    $diff_order_price = diffOrderPrice($price_precision);
    $side == 'BUY' ? $diff_order_price = -($diff_order_price) : false;
    $price = round($last_price + $diff_order_price, $price_precision);
    
    $timestamp = intval(microtime(true) * 1000);
    $query=[
        'symbol' => $symbol,
        'side' => $side,
        'type' => 'LIMIT',
        'timeInForce' => 'GTC',
        'quantity' => $quantity,
        'price' => $price,
        'timestamp' => $timestamp,
    ];
    
    $query_string = http_build_query($query);
    
    // generate signature
    $signature = signature($query_string, $api_secret);
    
    // insert timestamps and signature to query params
    $query['signature'] = $signature;
    
    // request to get price real-time
    $client_trade_new_order = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_trade_new_order = $client_trade_new_order->request('POST', '/fapi/v1/order', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_trade_new_order = json_decode($request_trade_new_order->getBody());
    return $response_trade_new_order;
}

function requestTakeProfit($symbol, $side, $tp_percent)
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    
    // define data
    $tp_percent = (float)$tp_percent;
    $last_price = (float)requestTicker($symbol)->lastPrice;
    $price_precision = getPrecision($symbol)->price_precision;
    $side == 'BUY' ? 
    $stop_price = round($last_price - ($tp_percent/100*$last_price), $price_precision): 
    $stop_price = round($last_price + ($tp_percent/100*$last_price), $price_precision);
    
    $timestamp = intval(microtime(true) * 1000);    
    $query=[
        'symbol' => $symbol,
        'side' => $side,
        'type' => 'TAKE_PROFIT_MARKET',
        'stopPrice' => $stop_price,
        'closePosition' => 'true',
        'timestamp' => $timestamp,
    ];
    
    $query_string = http_build_query($query);

    // generate signature
    $signature = signature($query_string, $api_secret);

    // insert signature to query params
    $query['signature'] = $signature;

    // request to get price real-time
    $client_take_profit = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_take_profit = $client_take_profit->request('POST', '/fapi/v1/order', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_take_profit = json_decode($request_take_profit->getBody());
    return $response_take_profit;
}

function requestMultipleOrders($symbol, $side, $amount, $tp_percent)
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    
    // define data order
    $amount = (float)$amount;
    $last_price = (float)requestTicker($symbol)->lastPrice;
    $precision = getPrecision($symbol);
    $price_precision = $precision->price_precision;
    $quantity_precision = $precision->quantity_precision;
    $quantity = round($amount/$last_price, $quantity_precision);
    // $price = round($last_price + diffOrderPrice($price_precision), $price_precision);

    // LIMIT
    // $query_order=(object)[
    //     'symbol' => $symbol,
    //     'side' => $side,
    //     'type' => 'LIMIT',
    //     'timeInForce' => 'GTC',
    //     'quantity' => (string)$quantity,
    //     'price' => (string)$price,
    // ];
    // MARKET
    $query_order=(object)[
        'symbol' => $symbol,
        'side' => $side,
        'type' => 'MARKET',
        'quantity' => (string)$quantity,
        'workingType' => 'MARK_PRICE',
    ];
    
    // define take profit
    $tp_percent = (float)$tp_percent;
    if($side == 'BUY'){
        $side_profit = 'SELL';
        $stop_price = round($last_price + ($tp_percent/100*$last_price), $price_precision);    
    } else {
        $side_profit = 'BUY';
        $stop_price = round($last_price - ($tp_percent/100*$last_price), $price_precision);
    }
    
    // TAKE_PROFIT_MARKET
    $query_take_profit=(object)[
        'symbol' => $symbol,
        'side' => $side_profit,
        'type' => 'TAKE_PROFIT_MARKET',
        'stopPrice' => (string)$stop_price,
        'closePosition' => 'true',
        'priceProtect' => 'true',
        'workingType' => 'MARK_PRICE',
    ];
    // STOP_MARKET
    // $query_take_profit=(object)[
    //     'symbol' => $symbol,
    //     'side' => $side_profit,
    //     'type' => 'STOP_MARKET',
    //     'stopPrice' => (string)$stop_price,
    //     'closePosition' => 'true',
    // ];
    
    //define batchOrders
    $batch_orders = json_encode([$query_order, $query_take_profit]); 
    $timestamp = intval(microtime(true) * 1000);
    
    $query = [
        'batchOrders' => $batch_orders,
        'timestamp' => $timestamp,
    ];
    
    $query_string = http_build_query($query);
    
    // generate signature
    $signature = signature($query_string, $api_secret);
    
    // insert timestamps and signature to query params
    $query['signature'] = $signature;

    // request to get price real-time
    $client_batch_orders = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_batch_orders = $client_batch_orders->request('POST', '/fapi/v1/batchOrders', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_batch_orders = json_decode($request_batch_orders->getBody());
    insertLogOrder('logs/trade/trade.log', $response_batch_orders);
    return $response_batch_orders;
}

function getPrefixRedis($prefix)
{
    $result = Redis::keys($prefix);
    return $result;
}

function getRedis($key)
{
    $result = Redis::get($key);
    $result = json_decode($result);
    return $result;
}

function setRedis($key, $value)
{
    Redis::set($key, json_encode($value));
}

function updateRedis($key, $value)
{
    Redis::del($key);
    Redis::set($key, json_encode($value));
}

function deleteRedis($key)
{
    Redis::del($key);
}

function insertLogAsset($path, $data)
{
    Log::build([
        'driver' => 'daily',
        'path' => storage_path($path),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 14,
    ])->info(json_encode($data));
}

function insertLogOrder($path, $data)
{
    Log::build([
        'driver' => 'daily',
        'path' => storage_path($path),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 14,
    ])->info(json_encode($data));
}