<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

function testFunction()
{
    return 'masuk';
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
    
    Log::channel('cryptomarket')->info(json_encode($response_klines));

    return $response_klines;
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