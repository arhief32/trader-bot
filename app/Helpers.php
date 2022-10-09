<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Info;
use App\Models\PositionRisk;
use App\Models\Order;

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

    // save to db
    foreach($response_exchange_info->symbols as $row_symbols)
    {
        Info::create([
            'symbol' => $row_symbols->symbol,
            'pair' => $row_symbols->pair,
            'base_asset' => $row_symbols->baseAsset,
            'quote_asset' => $row_symbols->quoteAsset,
            'margin_asset' => $row_symbols->marginAsset,
            'price_precision' => $row_symbols->pricePrecision,
            'quantity_precision' => $row_symbols->quantityPrecision,
            'base_asset_precision' => $row_symbols->baseAssetPrecision,
            'quote_precision' => $row_symbols->quotePrecision,
        ]);
    }

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
    // return getRedis('PRECISION_'.$symbol);
    return Info::where('symbol', $symbol)->first();
}

function diffOrderPrice(int $price_precision)
{
    switch ($price_precision) {
        case 1:
            return (float)1;
            break;
        case 2:
            return (float)0.1;
            break;
        case 3:
            return (float)0.01;
            break;
        case 4:
            return (float)0.005;
            break;
        case 5:
            return (float)0.0010;
            break;
        case 6:
            return (float)0.00015;
            break;
        case 7:
            return (float)0.000020;
            break;
        case 8:
            return (float)0.0000025;
            break;
        case 9:
            return (float)0.00000030;
            break;
        default:
            return (float)0.000000035;
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
            'timestamp' => (int)$row_klines[0],
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
        'recvWindow' => env('BINANCE_RECVWINDOW'),
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
        'recvWindow' => env('BINANCE_RECVWINDOW'),
    ];
    $query_string = http_build_query($query);
    
    // generate signature
    $signature = signature($query_string, $api_secret);
    
    // insert signature to query params
    $query['signature'] = $signature;
    
    // request to get price real-time
    $client_position_risk = new \GuzzleHttp\Client([
        'base_uri' => env('BINANCE_FUTURES_URL'),
        'verify'=> false,
        'debug' => false, // optional
    ]);
    $request_position_risk = $client_position_risk->request('GET', '/fapi/v2/positionRisk', [
        'headers' => [
            'X-MBX-APIKEY' => $api_key,
        ],
        'query' => $query,
    ]);
    $response_position_risk = json_decode($request_position_risk->getBody());

    // truncate
    PositionRisk::truncate();
    // save to db
    foreach($response_position_risk as $row_position_risk){
        if($row_position_risk->positionAmt != 0){
            PositionRisk::create([
                'symbol' => $row_position_risk->symbol,
                'position_amount' => $row_position_risk->positionAmt,
                'entry_price' => $row_position_risk->entryPrice,
                'mark_price' => $row_position_risk->markPrice,
                'unrealized_profit' => $row_position_risk->unRealizedProfit,
                'liquidation_price' => $row_position_risk->liquidationPrice,
                'leverage' => $row_position_risk->leverage,
                'max_notional_value' => $row_position_risk->maxNotionalValue,
                'margin_type' => $row_position_risk->marginType,
                'isolated_margin' => $row_position_risk->isolatedMargin,
                'is_auto_add_margin' => $row_position_risk->isAutoAddMargin,
                'position_side' => $row_position_risk->positionSide,
                'notional' => $row_position_risk->notional,
                'isolated_wallet' => $row_position_risk->isolatedWallet,
                'update_time' => $row_position_risk->updateTime,
            ]);
        }
    }

    return $response_position_risk;
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
    // $price = round($last_price + $diff_order_price, $price_precision);
    
    $timestamp = intval(microtime(true) * 1000);
    // $query_order = [
    //     'symbol' => $symbol,
    //     'side' => $side,
    //     'type' => 'LIMIT',
    //     'timeInForce' => 'GTC',
    //     'quantity' => $quantity,
    //     'price' => $price,
    //     'timestamp' => $timestamp,
    // ];
    // MARKET
    $query_order= [
        'symbol' => $symbol,
        'side' => $side,
        'type' => 'MARKET',
        'quantity' => (string)$quantity,
        'workingType' => 'MARK_PRICE',
        'recvWindow' => env('BINANCE_RECVWINDOW'),
        'timestamp' => $timestamp,
    ];
    
    $query_string = http_build_query($query_order);
    
    // generate signature
    $signature = signature($query_string, $api_secret);
    
    // insert timestamps and signature to query params
    $query_order['signature'] = $signature;
    
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
        'query' => $query_order,
    ]);
    $response_trade_new_order = json_decode($request_trade_new_order->getBody());
    insertLogOrder('logs/trade/trade.log', $response_trade_new_order);
    
    // save to db
    Order::create([
        'client_order_id' => $response_trade_new_order->clientOrderId,
        'average_price' => $response_trade_new_order->avgPrice,
        'close_position' => $response_trade_new_order->closePosition,
        'cumulative_quantity' => $response_trade_new_order->cumQty,
        'cumulative_quote' => $response_trade_new_order->cumQuote,
        'executed_quantity' => $response_trade_new_order->executedQty,
        'order_id' => $response_trade_new_order->orderId,
        'origin_quantity' => $response_trade_new_order->origQty,
        'origin_type' => $response_trade_new_order->origType,
        'position_side' => $response_trade_new_order->positionSide,
        'price' => $response_trade_new_order->price,
        'price_protect' => $response_trade_new_order->priceProtect,
        'reduce_only' => $response_trade_new_order->reduceOnly,
        'side' => $response_trade_new_order->side,
        'status' => $response_trade_new_order->status,
        'stop_price' => $response_trade_new_order->stopPrice,
        'symbol' => $response_trade_new_order->symbol,
        'time_in_force' => $response_trade_new_order->timeInForce,
        'type' => $response_trade_new_order->type,
        'working_type' => $response_trade_new_order->workingType,
    ]);

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
        'recvWindow' => env('BINANCE_RECVWINDOW'),
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
        'recvWindow' => env('BINANCE_RECVWINDOW'),
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
        'recvWindow' => env('BINANCE_RECVWINDOW'),
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

function insertLogIndicator($data)
{
    Log::build([
        'driver' => 'daily',
        'path' => storage_path('logs/indicator/indicator.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 14,
    ])->info(json_encode($data));
}

function insertLogOrder($data)
{
    Log::build([
        'driver' => 'daily',
        'path' => storage_path('logs/order/order.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 14,
    ])->info(json_encode($data));
}