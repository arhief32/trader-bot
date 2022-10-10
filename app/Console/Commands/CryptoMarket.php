<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PositionRisk;
use App\Models\MACD;
use App\Models\EMA;

class CryptoMarket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:cryptomarket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // get all assets from env
        // $assets = env('coins');
        // $assets = explode(',', $assets);
        $symbols = ['GALAUSDT', 'XLMUSDT', 'MKRUSDT'];

        // update position risk
        requestPositionRisk();

        foreach ($symbols as $symbol) {
            // get data current asset
            $interval = '1m';
            $limit = '60';
            $response_klines = requestKlines($symbol, $interval, $limit);

            // // macd
            // $macd = MACD::calculate($response_klines);
            $ema = EMA::calculate($response_klines, 7, 25);
            $last_klines = end($ema);

            // insert log each asset
            $log_path = 'logs/cryptomarket/' . $symbol . '/' . $symbol . '.log';
            insertLogAsset($log_path, $last_klines);
            insertLogIndicator([
                'symbol' => $symbol,
                'ema' => $last_klines['ema'],
            ]);

            // if position risk from binance/db is 0
            $position_risk = PositionRisk::where('symbol', $symbol)->first();
            if ($position_risk == false) {
                if ($last_klines['ema']['status'] == 'SELL') {
                    // requestTradeNewOrder($symbol, 'SELL', env('AMOUNT'));
                    requestMultipleOrders($symbol, 'SELL', env('AMOUNT'), env('TP_PERCENT'));
                }
                elseif ($last_klines['ema']['status'] == 'BUY') {
                    // requestTradeNewOrder($symbol, 'BUY', env('AMOUNT'));
                    requestMultipleOrders($symbol, 'BUY', env('AMOUNT'), env('TP_PERCENT'));
                }
            }

            if ($position_risk == true) {
                // close position first
                if ($position_risk->position_amount > 0 && $last_klines['ema']['status'] == 'SELL') {
                    $close_position = requestTradeNewOrder($symbol, 'SELL', env('AMOUNT'));
                    requestCancelOrder($symbol);
                    if (isset($close_position->clientOrderId)) {
                        // requestTradeNewOrder($symbol, 'SELL', env('AMOUNT'));
                        requestMultipleOrders($symbol, 'SELL', env('AMOUNT'), env('TP_PERCENT'));
                    }
                }
                else if ($position_risk->position_amount < 0 && $last_klines['ema']['status'] == 'BUY') {
                    $close_position = requestTradeNewOrder($symbol, 'BUY', env('AMOUNT'));
                    requestCancelOrder($symbol);
                    if (isset($close_position->clientOrderId)) {
                        // requestTradeNewOrder($symbol, 'BUY', env('AMOUNT'));
                        requestMultipleOrders($symbol, 'BUY', env('AMOUNT'), env('TP_PERCENT'));
                    }
                }
            }
        }
    }
}