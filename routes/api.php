<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

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