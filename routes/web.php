<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('chart', function(Request $request){
    $klines = requestKlines($request->symbol, '1m', 300);
    $array_klines = [];
    foreach($klines as $row_klines){
        array_push($array_klines, [
            'x' => $row_klines['timestamp'],
            'y' => [
                (float)$row_klines['open'],
                (float)$row_klines['high'],
                (float)$row_klines['low'],
                (float)$row_klines['close'],
            ],
        ]);
    }
    return view('chart', ['name' => $request->symbol, 'data' => $array_klines]);
});

Route::get('ema', function(Request $request){
    $klines = requestKlines($request->symbol, '1m', 300);
    $array_klines = [];
    foreach($klines as $row_klines){
        array_push($array_klines, [
            'x' => $row_klines['timestamp'],
            'y' => [
                (float)$row_klines['open'],
                (float)$row_klines['high'],
                (float)$row_klines['low'],
                (float)$row_klines['close'],
            ],
        ]);
    }
    return view('ema', ['name' => $request->symbol,'data' => $array_klines]);
});