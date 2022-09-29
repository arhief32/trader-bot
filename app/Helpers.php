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
    return $response_klines;
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

function requestTradeNewOrder($symbol, $side, $amount)
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    $timestamp = intval(microtime(true) * 1000);    
    
    // define data
    $amount = (float)$amount;
    $last_price = (float)requestTicker($symbol)->lastPrice;
    $quantity_precision = getPrecision($symbol)->quantity_precision;
    $quantity = round($amount/$last_price, $quantity_precision);
    
    $query=[
        'symbol' => $symbol,
        'side' => $side,
        'type' => 'MARKET',
        'quantity' => $quantity,
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
    $request_account_information = $client_account_information->request('POST', '/fapi/v1/order', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_account_information = json_decode($request_account_information->getBody());
    return $response_account_information;
}

function requestTakeProfit($symbol, $side, $tp_percent)
{
    // define variable
    $api_key = env('BINANCE_API_KEY');
    $api_secret = env('BINANCE_API_SECRET');
    $timestamp = intval(microtime(true) * 1000);    

    // define data
    $tp_percent = (float)$tp_percent;
    $last_price = (float)requestTicker($symbol)->lastPrice;
    $price_precision = getPrecision($symbol)->price_precision;
    $side == 'BUY' ? 
    $stop_price = round($last_price - ($tp_percent/100*$last_price), $price_precision): 
    $stop_price = round($last_price + ($tp_percent/100*$last_price), $price_precision);
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
    $client_account_information = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_account_information = $client_account_information->request('POST', '/fapi/v1/order', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_account_information = json_decode($request_account_information->getBody());
    return $response_account_information;
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