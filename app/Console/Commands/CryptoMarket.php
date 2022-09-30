<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
        $assets = env('coins');
        $assets = explode(',', $assets);
        
        foreach($assets as $asset){
            // get data current asset
            $interval = '1m';
            $limit = '60';
            $response_klines = requestKlines($asset, $interval, $limit);

            // // calculate indicator
            $data_market = [];
            if($response_klines != null || $response_klines != 0){
                foreach($response_klines as $price){
                    array_push($data_market, $price[4]);
                }
            }
            // update market in redis (del and then set)
            updateRedis('MARKET_'.$asset, $data_market);

            // rsi
            $rsi = trader_rsi($data_market, 6);
            isset($rsi[6]) ? $rsi = $rsi[6] : $rsi = false;
            // macd
            $macd = MACD::processMacdData($asset);
            $macd = end($macd);
            
            // insert log each asset
            $log_path = 'logs/cryptomarket/'.$asset.'/'.$asset.'.log';
            $log_data = [
                'price_usd' => end($data_market),
                'rsi' => $rsi,
                'macd' => $macd,
            ];
            insertLogAsset($log_path, $log_data);
        }
    }
}