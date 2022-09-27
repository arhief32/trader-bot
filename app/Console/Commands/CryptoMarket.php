<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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
        // $assets = env('ASSETS');
        // $assets = explode(',', $assets);
        // $assets = ['bitcoin','ethereum','binance-coin','xrp','cardano','solana','terra-luna','dogecoin','polkadot','polygon','shiba-inu','tron','avalanche','ethereum-classic','litecoin','cosmos','chainlink','monero','algorand','chiliz'];
        $assets = ['BTCUSDT','ETHUSDT','BNBUSDT','XRPUSDT','ADAUSDT','SOLUSDT','LUNAUSDT','DOGEUSDT','DOTUSDT','MATICUSDT','TRXUSDT','AVAXUSDT','ETCUSDT','LTCUSDT','ATOMUSDT','LINKUSDT','XMRUSDT','ALGOUSDT','CHZUSDT'];
        
        foreach($assets as $asset){
            // request to get price real-time
            $client_klines = new \GuzzleHttp\Client([
                'base_uri' => 'https://fapi.binance.com/api',
                'verify'=> false,
                'debug' => false, // optional
            ]);
            $request_klines = $client_klines->request('GET', '/fapi/v1/klines', [
                'query' => [
                    'symbol' => $asset,
                    'interval' => '1m',
                    'limit' => '60',
                ],
            ]);
            $response_klines = json_decode($request_klines->getBody());
            
            Log::channel('cryptomarket')->info(json_encode($response_klines));

            // // calculate indicator
            $data_market = [];
            if($response_klines != null || $response_klines != 0){
                foreach($response_klines as $price){
                    array_push($data_market, $price[4]);
                }
            }
            // rsi
            $rsi = trader_rsi($data_market, 6);
            isset($rsi[6]) ? $rsi = $rsi[6] : $rsi = false;
            // macd
            $macd = trader_macd($data_market, 12, 26,9);
            
            // update market in redis (del and then set)
            Redis::del('market_'.$asset);
            Redis::set('market_'.$asset, json_encode($data_market));

            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/cryptomarket/'.$asset.'/'.$asset.'.log'),
                'level' => env('LOG_LEVEL', 'info'),
                'days' => 14,
            ])->info(json_encode([
                'price_usd' => end($data_market),
                'rsi' => $rsi,
                'macd' => $macd[0]['57'],
            ]));
        }
    }
}