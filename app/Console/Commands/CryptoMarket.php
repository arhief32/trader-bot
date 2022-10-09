<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PositionRisk;
use App\Models\MACD;

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
        $symbols = ['XLMUSDT', 'XRPUSDT', 'UNFIUSDT', 'XMRUSDT', 'KAVAUSDT', 'ETHUSDT', 'BTCUSDT', 'MKRUSDT', 'RVNUSDT', 'COMPUSDT'];

        // update position risk
        requestPositionRisk();
        
        foreach ($symbols as $symbol) {
            // get data current asset
            $interval = '1m';
            $limit = '60';
            $response_klines = requestKlines($symbol, $interval, $limit);

            // // macd
            $macd = MACD::calculate($response_klines);
            $last_macd = end($macd);

            // insert log each asset
            $log_path = 'logs/cryptomarket/' . $symbol . '/' . $symbol . '.log';
            insertLogAsset($log_path, $last_macd);
            insertLogIndicator([
                'symbol' => $symbol,
                'macd' => $last_macd['macd'],
            ]);

			// if position risk from binance/db is 0
            $position_risk = PositionRisk::where('symbol', $symbol)->first();
            if($position_risk == false){
                if($last_macd['macd']['status'] == 'SELL'){
                    requestTradeNewOrder($symbol, 'SELL', env('AMOUNT'));
                } elseif($last_macd['macd']['status'] == 'BUY'){
                    requestTradeNewOrder($symbol, 'BUY', env('AMOUNT'));
                }
            }

            if($position_risk == true){
                if($last_macd['macd']['status'] == 'SELL' || $last_macd['macd']['status'] == 'BUY'){
					// close position first
                    if($position_risk->position_amount > 0){
                        requestTradeNewOrder($symbol, 'SELL', env('AMOUNT'));
                    } else {
                        requestTradeNewOrder($symbol, 'BUY', env('AMOUNT'));
                    }
                }

				// enter position when condition status SELL or BUY
                if($last_macd['macd']['status'] == 'SELL'){
                    requestTradeNewOrder($symbol, 'SELL', env('AMOUNT'));
                } elseif($last_macd['macd']['status'] == 'BUY'){
                    requestTradeNewOrder($symbol, 'BUY', env('AMOUNT'));
                }
            }
        }
    }
}